<?php

declare(strict_types=1);

namespace atc\WHx4\Core\Query;

use WP_Query;
use atc\WHx4\Core\Contracts\PluginContext; //???
use atc\WHx4\Core\Contracts\QueryContributor;
use atc\WHx4\Query\ScopedQueryBuilder;

final class PostQuery
{
    public function __construct(
        private PluginContext $ctx,
        private ?ScopedQueryBuilder $scopes = null
    ) {
        //$this->scopes = $this->scopes ?: new ScopedQueryBuilder();
    }

    /**
     * @param array<string,mixed> $params
     *   Expected keys (examples): post_type, scope, per_page, paged,
     *   date_start, date_end, tax, meta, orderby, order.
     * @return array{posts: \WP_Post[], found: int, max_pages: int}
     */
    public function find(array $params): array
    {
        $args = $this->normalize($params);

        // Apply scope helpers (today/this_week/etc.)
        if (!empty($params['scope'])) {
            $args = $this->scopes->apply($args, (string)$params['scope']);
        }

        // Allow the active CPT handler to refine args
        $ptype = (string)($args['post_type'] ?? '');
        $handlerClass = $this->ctx->getActivePostTypes()[$ptype] ?? null;
        if ($handlerClass && is_a($handlerClass, QueryContributor::class, true)) {
            /** @var QueryContributor $contrib */
            $contrib = new $handlerClass();
            $args = $contrib->adjustQueryArgs($args, $params);
        }

        /**
         * Final, global escape hatch (site-level).
         * Filter name keeps your prefix and allows per-type specialization.
         */
        $args = apply_filters('rex_query_args', $args, $params);
        $args = apply_filters("rex_query_args_{$ptype}", $args, $params);

        $q = new WP_Query($args);

        return [
            'posts'     => $q->posts,
            'found'     => (int)$q->found_posts,
            'max_pages' => (int)$q->max_num_pages,
        ];
    }

    /** @param array<string,mixed> $params */
    private function normalize(array $params): array
    {
        $ptype = isset($params['post_type']) ? (string)$params['post_type'] : 'post';

        // Only allow enabled types
        $enabled = array_keys($this->ctx->getActivePostTypes());
        if (!in_array($ptype, $enabled, true)) {
            // Fallback to 'post' (or throw) — your call:
            $ptype = 'post';
        }

        $perPage = isset($params['per_page']) ? max(1, (int)$params['per_page']) : 10;
        $paged   = isset($params['paged']) ? max(1, (int)$params['paged']) : 1;

        $args = [
            'post_type'      => $ptype,
            'posts_per_page' => $perPage,
            'paged'          => $paged,
            'orderby'        => $params['orderby'] ?? 'date',
            'order'          => $params['order'] ?? 'DESC',
            'post_status'    => $params['post_status'] ?? 'publish',
        ];

        // Optional tax & meta
        if (!empty($params['tax']) && is_array($params['tax'])) {
            $args['tax_query'] = $params['tax'];
        }
        if (!empty($params['meta']) && is_array($params['meta'])) {
            $args['meta_query'] = $params['meta'];
        }

        return $args;
    }
}
