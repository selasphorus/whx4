<?php

declare(strict_types=1);

namespace atc\WHx4\Core\Query;

use atc\WHx4\Core\Query\QueryHelpers;

/**
 * MetaQueryBuilder
 *
 * Purpose (v1): Convert a normalized, framework-agnostic "meta spec" into a
 * WordPress-ready `meta_query` array. This class is intentionally stateless
 * and performs no WordPress calls—only array assembly.
 *
 * Scope: Simple flat groups only (root relation AND/OR). No nested groups
 * except for the special overlapRange helper, which returns its own grouped
 * subclauses (AND).
 *
 * Responsibility boundaries:
 * - Casting decisions (DATE vs DATETIME vs NUMERIC) are *provided* by callers.
 * - Value normalization (e.g., inclusive end date) is decided upstream (e.g., ScopedDateResolver).
 * - We only assemble arrays in the shape WP_Query expects.
 */
final class MetaQueryBuilder
{
    /**
     * Public entry point: build the `meta_query` array from a normalized spec.
     *
     * Spec format (MVP):
     * [
     *   'relation' => 'AND'|'OR',              // optional, defaults to 'AND'
     *   'clauses'  => [
     *     [ 'type' => 'equals'|'in'|'like'|'gte'|'lte'|'range'|'exists'|'notExists'|'overlapRange'|'custom', ... ],
     *     ...
     *   ]
     * ]
     *
     * @param array{relation?:'AND'|'OR',clauses?:list<array<string,mixed>>} $spec
     * @return array WordPress-ready meta_query array (or empty array if nothing valid).
     */
    public static function build(array $spec): array
    {
        $relation = QueryHelpers::normalizeRelation($spec['relation'] ?? 'AND');
        $clauses  = $spec['clauses'] ?? [];

        $built = [];
        foreach ($clauses as $clauseSpec) {
            $clause = self::makeClause($clauseSpec);
            if ($clause === null) {
                continue; // Skip invalid/unknown clauses silently (debug logging belongs elsewhere).
            }
            // overlapRange yields a grouped payload under __group
            $built[] = $clause['__group'] ?? $clause;
        }

        if ($built === []) {
            return [];
        }

        // WP expects: ['relation' => 'AND'|'OR', 0 => clause, 1 => clause, ...]
        return array_merge(['relation' => $relation], $built);
    }

    /**
     * Route a spec to the appropriate builder.
     *
     * @param array<string,mixed> $spec
     * @return array<string,mixed>|null Null when missing required fields or unknown type.
     */
    private static function makeClause(array $spec): ?array
    {
        $type = isset($spec['type']) ? (string)$spec['type'] : '';

        switch ($type) {
            case 'equals':
                // key = value
                return self::makeSimpleComparison($spec, '=');

            case 'gte':
                // key >= value
                return self::makeSimpleComparison($spec, '>=');

            case 'lte':
                // key <= value
                return self::makeSimpleComparison($spec, '<=');

            case 'in':
                // key IN (values[])
                if (!self::requireFields($spec, ['key', 'value']) || !is_array($spec['value']) || $spec['value'] === []) {
                    return null;
                }
                return self::assembleClause(
                    (string)$spec['key'],
                    'IN',
                    array_values($spec['value']),
                    self::normalizeCast($spec)
                );

            case 'like':
                // key LIKE %value%
                if (!self::requireFields($spec, ['key']) || !array_key_exists('value', $spec)) {
                    return null;
                }
                return self::assembleClause(
                    (string)$spec['key'],
                    'LIKE',
                    '%' . (string)$spec['value'] . '%',
                    self::normalizeCast($spec)
                );

            case 'range':
                // key BETWEEN min AND max (inclusive)
                if (!self::requireFields($spec, ['key']) || !array_key_exists('min', $spec) || !array_key_exists('max', $spec)) {
                    return null;
                }
                return self::assembleClause(
                    (string)$spec['key'],
                    'BETWEEN',
                    [$spec['min'], $spec['max']],
                    self::normalizeCast($spec)
                );

            case 'exists':
                // EXISTS (no value/type)
                if (!self::requireFields($spec, ['key'])) {
                    return null;
                }
                return self::assembleClause((string)$spec['key'], 'EXISTS');

            case 'notExists':
                // NOT EXISTS (no value/type)
                if (!self::requireFields($spec, ['key'])) {
                    return null;
                }
                return self::assembleClause((string)$spec['key'], 'NOT EXISTS');

            case 'overlapRange':
                // (start_key <= end) AND (end_key >= start) AND start_key EXISTS AND end_key EXISTS
                return self::makeOverlapGroup($spec);

            case 'custom':
                // Raw WP meta_query clause passthrough
                $raw = $spec['raw'] ?? null;
                return is_array($raw) && $raw !== [] ? $raw : null;

            default:
                return null; // Unknown type for v1
        }
    }

    /**
     * Build a simple comparison (equals/gte/lte): key {op} value.
     *
     * @param array{key?:string,value?:mixed,cast?:string} $spec
     */
    private static function makeSimpleComparison(array $spec, string $op): ?array
    {
        if (!self::requireFields($spec, ['key']) || !array_key_exists('value', $spec)) {
            return null;
        }
        return self::assembleClause(
            (string)$spec['key'],
            $op,
            $spec['value'],
            self::normalizeCast($spec)
        );
    }

    /**
     * Build the overlapRange group:
     * AND[
     *   start_key <= end (type applied if provided),
     *   end_key   >= start (type applied if provided),
     *   start_key EXISTS,
     *   end_key   EXISTS
     * ]
     *
     * @param array{start_key?:string,end_key?:string,start?:mixed,end?:mixed,cast?:string} $spec
     * @return array{__group:array<string,mixed>}|null
     */
    private static function makeOverlapGroup(array $spec): ?array
    {
        if (!self::requireFields($spec, ['start_key', 'end_key', 'start', 'end'])) {
            return null;
        }

        $type = self::normalizeCast($spec);
        $group = ['relation' => 'AND'];

        // start_key <= end
        $group[] = self::assembleClause((string)$spec['start_key'], '<=', $spec['end'], $type);

        // end_key >= start
        $group[] = self::assembleClause((string)$spec['end_key'], '>=', $spec['start'], $type);

        // Key existence guards
        $group[] = self::assembleClause((string)$spec['start_key'], 'EXISTS');
        $group[] = self::assembleClause((string)$spec['end_key'], 'EXISTS');

        return ['__group' => $group];
    }

    /**
     * The one true place where a WP-style meta_query clause array is assembled.
     * - Includes 'value' only when meaningful (not for EXISTS/NOT EXISTS).
     * - Includes 'type' only when provided and relevant (not for EXISTS/NOT EXISTS).
     *
     * @param mixed $value
     * @return array<string,mixed>
     */
    private static function assembleClause(string $key, string $compare, $value = null, ?string $type = null): array
    {
        $clause = [
            'key'     => $key,
            'compare' => $compare,
        ];

        $needsValue = !in_array($compare, ['EXISTS', 'NOT EXISTS'], true);
        if ($needsValue) {
            $clause['value'] = $value;
            if ($type !== null) {
                $clause['type'] = $type;
            }
        }

        return $clause;
    }

    /**
     * Extract optional cast (WP 'type') if present.
     *
     * @param array<string,mixed> $spec
     */
    private static function normalizeCast(array $spec): ?string
    {
        return isset($spec['cast']) ? (string)$spec['cast'] : null;
    }

    /**
     * Merge multiple MetaQueryBuilder specs into a single flat spec.
     * v1 semantics: all clauses are flattened under a single **root relation** (default AND).
     * NOTE: Child spec relations (e.g., 'OR') are NOT preserved in v1 — if you need OR,
     * build that as a single spec before merging.
     *
     * @param array<int,array{relation?:'AND'|'OR',clauses?:array<int,array<string,mixed>>}> $specs
     * @param 'AND'|'OR' $relation Root relation for the merged spec (default 'AND').
     * @return array{relation:'AND'|'OR',clauses:array<int,array<string,mixed>>} | [] // normalized spec or []
     */
    public static function mergeSpecs(array $specs, string $relation = 'AND'): array
    {
        $rootRelation = strtoupper(trim($relation)) === 'OR' ? 'OR' : 'AND';

        $merged = ['relation' => $rootRelation, 'clauses' => []];

        foreach ($specs as $spec) {
            if (!is_array($spec) || empty($spec['clauses']) || !is_array($spec['clauses'])) {
                continue;
            }
            foreach ($spec['clauses'] as $clause) {
                $merged['clauses'][] = $clause;
            }
        }

        return $merged['clauses'] ? $merged : [];
    }

}
