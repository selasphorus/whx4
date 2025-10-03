<?php

declare(strict_types=1);

namespace atc\WHx4\Core\Query;

use WP_Query;
use atc\WHx4\Core\WHx4;
//use atc\WHx4\Core\Contracts\QueryContributor;
//use atc\WHx4\Query\ScopedDateResolver;
//use atc\WHx4\Http\UrlParamBridge;

final class PostQuery
{
    /**
     * @param array<string,mixed> $params
     *   Expected keys (examples): post_type, scope, posts_per_page, paged,
     *   date_start, date_end, tax, meta, orderby, order.
     *
     * @param array{
     *   post_type?:string,
     *   post_status?:string,
     *   paged?:int,
     *   posts_per_page?:int,
     *   order?:'ASC'|'DESC'|string,
     *   orderby?:string,
     *   meta_key?:string|null,
     *   // date scope
     *   scope?:string|array|null,
     *   date_meta?:array{
     *     key?:string,
     *     start_key?:string,
     *     end_key?:string,
     *     data_type?:'DATE'|'DATETIME'|'NUMERIC'|string
     *   },
     *   // tax filters
     *   tax?:array<string,array<int,string>>
     * } $params
     *
     * @return array{ posts: \WP_Post[], found: int, max_pages: int, args: array }
     */
    public function find(array $params): array
    {
        $p = $this->normalizeContract($params);

        // Allow the active CPT handler to refine args -- ???
        $ptype = $p['post_type'];
        $handlerClass = WHx4::ctx()->getActivePostTypes()[$ptype] ?? null;
        if ($handlerClass && is_a($handlerClass, QueryContributor::class, true)) {
            /** @var QueryContributor $contrib */
            $contrib = new $handlerClass();
            $args = $contrib->adjustQueryArgs($args, $p);
        }

        // Resolve scope (string or {start,end}) via ScopedDateResolver.
        $dateBounds = self::resolveScope($p['scope'], $p['date_meta']['data_type'] ?? null);
        //$dateMetaSpec  = self::dateMetaSpecFromBounds($dateMeta, $resolved);
        $dateMetaSpec  = self::dateMetaSpecFromBounds($p['date_meta'], $dateBounds);

        // Build combined meta_query spec
        $combinedMetaSpec = MetaQueryBuilder::mergeSpecs([$dateMetaSpec, $p['meta']], 'AND');
        $metaQuery        = MetaQueryBuilder::build($combinedMetaSpec);

        // 3) Build tax_query from simple map (taxonomy => [terms]) — uses TaxQueryBuilder when available.
        $taxMap    = $p['tax'] ?? [];
        $taxQuery  = TaxQueryBuilder::build($taxMap); //$taxQuery  = self::buildTaxQuery($taxMap);

        // 4) Assemble basic WP_Query args
        $args = [
            'post_type'      => $p['post_type'],
            'post_status'    => $p['post_status'],
            'paged'          => $p['paged'],
            'posts_per_page' => $p['limit'],
            'order'          => $p['order'],
            'orderby'        => $p['orderby'],
            'no_found_rows'  => false,
        ];

        if ( ($p['orderby'] === 'meta_value' || $p['orderby'] === 'meta_value_num') && $p['meta_key'] && $p['meta_key'] != '' ) {
            $args['meta_key'] = $p['meta_key'];
            if (!empty($p['date_meta']['data_type'])) {
                $mt = strtoupper(trim((string)$p['date_meta']['data_type']));
                if (in_array($mt, ['NUMERIC','BINARY','CHAR','DATE','DATETIME','DECIMAL','SIGNED','TIME','UNSIGNED'], true)) {
                    $args['meta_type'] = $mt;
                }
            }
        }

        if ($metaQuery !== []) {
            $args['meta_query'] = $metaQuery;
        }
        if ($taxQuery !== []) {
            $args['tax_query'] = $taxQuery;
        }

        /**
         * Final, global escape hatch (site-level).
         * Filter name keeps your prefix and allows per-type specialization.
         */
        // WIP!!!
        $args = apply_filters('whx4_query_args', $args, $p);
        $args = apply_filters("whx4_query_args_{$ptype}", $args, $p);

        // 5) Run the query.
        $q = new WP_Query($args);

        return [
            'posts'     => $q->posts ?: [],
            'found'     => (int)$q->found_posts,
            'max_pages' => (int)$q->max_num_pages,
            'args'      => $args,
            'query_request'=> $q->request,
        ];
    }

        /**
     * Normalize loose $params into a canonical PostQuery contract (spec only).
     * - Validates post type against active types (falls back to 'post').
     * - Coerces paging/limit and ordering.
     * - Normalizes scope (string | {start,end} | null).
     * - Normalizes date_meta (pass-through keys only).
     * - Normalizes user meta spec (MetaQueryBuilder format) to a safe default.
     * - Normalizes tax map (taxonomy => [slugs]).
     *
     * @param array{
     *   post_type?:string,
     *   post_status?:string,
     *   paged?:int|string,
     *   limit?:int|string,
     *   order?:string,
     *   orderby?:string,
     *   meta_key?:string|null,
     *   scope?:string|array|null,
     *   date_meta?:array{key?:string,start_key?:string,end_key?:string,data_type?:string},
     *   meta?:array,
     *   tax?:array<string,mixed>
     * } $params
     * @return array{
     *   post_type:string,
     *   post_status:string,
     *   paged:int,
     *   limit:int,
     *   order:string,
     *   orderby:string,
     *   meta_key:?string,
     *   scope:string|array|null,
     *   date_meta:array{key?:string,start_key?:string,end_key?:string,data_type?:string},
     *   meta:array,
     *   tax:array<string,array<int,string>>
     * }
     */
    private function normalizeContract(array $params): array
    {
        // 1) Post type must be active/enabled
        $ptype = isset($params['post_type']) ? (string)$params['post_type'] : 'post';
        $enabled = array_keys(WHx4::ctx()->getActivePostTypes());
        if (!in_array($ptype, $enabled, true)) {
            // Fallback to 'post' (or throw) — your call:
            $ptype = 'post';
        }

        // 2) Paging + limit (contract uses `limit`, NOT posts_per_page)
        $paged = isset($params['paged']) ? max(1, (int)$params['paged']) : 1; //$paged = max(1, (int)($params['paged'] ?? 1));

        // Prefer 'limit'; allow 'posts_per_page' as a backwards-compat alias if 'limit' not provided.
        if (isset($params['limit'])) {
            $limit = (int)$params['limit'];
        } elseif (isset($params['posts_per_page'])) {
            $limit = (int)$params['posts_per_page'];
        } else {
            $limit = 10;
        }
        $limit = max(1, $limit);

        // 3) Ordering
        $orderRaw = (string)($params['order'] ?? 'DESC');
        $order = strtoupper(trim($orderRaw));
        $order = in_array($order, ['ASC','DESC'], true) ? $order : 'DESC';
        $orderby = (string)($params['orderby'] ?? 'date'); // leave flexible for WP-supported values

        // 4) Status
        $postStatus = (string)($params['post_status'] ?? 'publish');

        // 5) meta_key hint (only meaningful when orderby=meta_value or meta_value_num)
        $metaKey = isset($params['meta_key']) && $params['meta_key'] !== '' ? (string)$params['meta_key'] : null;

        // 6) Scope normalization (string | {start,end} | null)
        // Accept string ("today", "this_week", ...) or array {start?, end?} or null
        $scope = $params['scope'] ?? null;
        if (is_string($scope)) {
            $scope = trim($scope) !== '' ? $scope : null;
        } elseif (is_array($scope)) {
            $s = $scope['start'] ?? null;
            $e = $scope['end'] ?? null;
            $scope = ($s !== null || $e !== null) ? ['start' => $s, 'end' => $e] : null;
        } else {
            $scope = null;
        }

        // 7) Date meta mapping (pass-through keys only; builders will validate further)
        $dateMetaIn = is_array($params['date_meta'] ?? null) ? $params['date_meta'] : [];
        $dateMeta = [];
        if (isset($dateMetaIn['key'])) {
            $dateMeta['key'] = (string)$dateMetaIn['key'];
        }
        if (isset($dateMetaIn['start_key'])) {
            $dateMeta['start_key'] = (string)$dateMetaIn['start_key'];
        }
        if (isset($dateMetaIn['end_key'])) {
            $dateMeta['end_key'] = (string)$dateMetaIn['end_key'];
        }
        if (isset($dateMetaIn['meta_type'])) {
            $dateMeta['meta_type'] = (string)$dateMetaIn['meta_type'];
        }
        if (isset($dateMetaIn['end_optional'])) {
            $dateMeta['end_optional'] = (bool)$dateMetaIn['end_optional'];
        }

        // 8) User-provided meta spec (MetaQueryBuilder format) or []
        $metaSpecIn = $params['meta'] ?? [];
        $metaSpec = (is_array($metaSpecIn) && isset($metaSpecIn['clauses']) && is_array($metaSpecIn['clauses']))
            ? $metaSpecIn
            : [];

        // 9) Taxonomy map: ensure "taxonomy => [slugs...]" (trimmed, non-empty) // ensure "taxonomy => [terms...]" (slugs by default)
        // check if isset($params['tax'])?
        $taxIn = is_array($params['tax'] ?? null) ? $params['tax'] : [];
        $taxOut = [];
        foreach ($taxIn as $taxonomy => $terms) {
            $list = is_array($terms) ? $terms : [$terms];
            $list = array_values(array_filter(array_map(
                static fn($t) => is_string($t) ? trim($t) : '',
                $list
            ), static fn($t) => $t !== ''));
            if ($list !== []) {
                $taxOut[(string)$taxonomy] = $list;
            }
        }

        $args = [
            'post_type'   => $ptype,
            'post_status' => $postStatus,
            'paged'       => $paged,
            'limit'       => $limit,
            'order'       => $order,
            'orderby'     => $orderby,
            'meta_key'    => $metaKey,
            'scope'       => $scope,
            'date_meta'   => $dateMeta,
            'meta'        => $metaSpec,
            'tax'         => $taxOut,
        ];

        return $args;
    }

    /**
     * Resolve a scope (string or {start,end}) into concrete date bounds.
     *
     * @param string|array|null $scopeSpec Named scope (e.g., 'today','this_week') or ['start'=>..,'end'=>..] or null
     * @param string|null $castHint Optional cast hint: 'DATE'|'DATETIME'|'NUMERIC' (only DATE vs DATETIME matters here)
     * @return array{start:mixed,end:mixed}|null
     */
    private static function resolveScope($scopeSpec, ?string $castHint): ?array
    {
        if ($scopeSpec === null || $scopeSpec === '' || $scopeSpec === []) {
            return null;
        }

        // Prefer DATE windowing when explicitly hinted; otherwise default to DATETIME.
        $mode = (is_string($castHint) && strtoupper(trim($castHint)) === 'DATE') ? 'DATE' : 'DATETIME';

        try {
            /** @var array{start:mixed,end:mixed} $bounds */
            $bounds = ScopedDateResolver::resolve($scopeSpec, ['mode'=>$mode]);
            return $bounds;
        } catch (\Throwable $e) {
            return null;
        }
    }
        /*
        $range = ScopedDateResolver::resolve($scope, [
            'mode' => 'DATE',              // or 'DATETIME' when you need time precision
            'year' => $year ?? null,       // if relevant (e.g., scope 'month')
            'month' => $month ?? null,     // if relevant
            // 'tz' => $tz,                // optional override
        ]);
        // $range['start'], $range['end'] are DateTimeImmutable|null*/

    /**
     * Build a minimal MetaQueryBuilder spec from date mapping + resolved window.
     *
     * Accepted mappings:
     * - ['key' => 'transaction_date', 'meta_type' => 'DATE'] + scope → range
     * - ['start_key' => 'start_date', 'end_key' => 'end_date', 'meta_type' => 'DATETIME'] + scope → overlapRange
     *
     * @param array{
     *   key?:string,
     *   start_key?:string,
     *   end_key?:string,
     *   cast?:string
     * } $dateMeta
     * @param array{start:mixed,end:mixed}|null $dateBounds
     * @return array{relation?:'AND'|'OR',clauses?:array<int,array<string,mixed>>}
     */
    private static function dateMetaSpecFromBounds(array $dateMeta, ?array $dateBounds): array
    {
        if ($dateBounds === null) {
            return []; // no date filtering requested
        }

        $metaType = isset($dateMeta['data_type']) ? (string)$dateMeta['data_type'] : null;

        // Range over a single date key.
        if (!empty($dateMeta['key'])) {
            return [
                'relation' => 'AND',
                'clauses'  => [[
                    'type' => 'range',
                    'key'  => (string)$dateMeta['key'],
                    'min'  => $dateBounds['start'],
                    'max'  => $dateBounds['end'],
                    'meta_type' => $metaType,
                ]],
            ];
        }

        // Overlap over start/end keys.
        if (!empty($dateMeta['start_key']) && !empty($dateMeta['end_key'])) {
            return [
                'relation' => 'AND',
                'clauses'  => [[
                    'type'       => 'overlapRange',
                    'start_key'  => (string)$dateMeta['start_key'],
                    'end_key'    => (string)$dateMeta['end_key'],
                    'start'      => $dateBounds['start'],
                    'end'        => $dateBounds['end'],
                    'meta_type'  => $metaType,
                    'end_optional' => !empty($dateMeta['end_optional']),
                ]],
            ];
        }

        return [];
    }

    /**
     * Translate a simple "taxonomy => [terms]" map into WP tax_query.
     * If you have a TaxQueryBuilder, swap this body with TaxQueryBuilder::build().
     *
     * @param array<string,array<int,string>> $taxMap
     * @return array
     */
    private static function buildTaxQuery(array $taxMap): array
    {
        if ($taxMap === []) {
            return [];
        }

        // If TaxQueryBuilder exists in your tree, prefer it:
        // return TaxQueryBuilder::build(['relation'=>'AND','clauses'=>...]);

        $out = ['relation' => 'AND'];
        foreach ($taxMap as $taxonomy => $terms) {
            $terms = array_values(array_filter($terms, static function($t) {
                return $t !== null && $t !== '';
            }));
            if ($terms === []) {
                continue;
            }
            $out[] = [
                'taxonomy' => (string)$taxonomy,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN',
            ];
        }

        return count($out) > 1 ? $out : [];
    }

    public static function fromRequest(string $targetHandlerClass, array $baseArgs, ?array $only = null, ?array $source = null): self
    {
        $source = $source ?? $_GET;
        $urlArgs = UrlParamBridge::fromSource($targetHandlerClass, $source, $only);
        $args = UrlParamBridge::merge($targetHandlerClass, $baseArgs, $urlArgs);
        return new self($args);
    }

}
