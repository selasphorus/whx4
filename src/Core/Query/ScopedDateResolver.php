<?php

declare(strict_types=1);

namespace atc\WHx4\Core\Query;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException; // ???
use atc\WHx4\Core\WHx4;
use atc\WHx4\Utils\Text;
use atc\WHx4\Utils\DateHelper;

class ScopedDateResolver
{
    /**
     * Resolve a named scope (e.g., 'today', 'this_week', 'last_year') into [start, end] DateTimeImmutable objects.
     * Open-ended spans are supported by returning null for start or end.
     * -- i.e. Resolve a scope to an inclusive [start, end] pair in site timezone.
     *
     * Options:
     * - now: DateTimeInterface (defaults to "now" in site timezone)
     * - year: int|null
     * - month: int|string|null (1-12 or "jan"/"january"/… — caller should normalize if passing string)
     * - inclusive_end: bool (default true) -> end normalized to end-of-day 23:59:59
     *
     * Extensibility:
     * - Filter 'whx4_scopes_register' can add/override scope resolvers.
     *
     * @param string $scope
     // OR
     * @param string|array<string,mixed> $scope  Named scope (e.g. 'this_week') or
     *                                           ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD?'] or
     *                                           ['year' => 2024] or ['years' => [2022, 2024]].
     ///
     * @param array<string,mixed>        $options {
     *     @var 'DATE'|'DATETIME' $mode     Output precision (affects end-of-day). Default 'DATE'.
     *     @var DateTimeImmutable  $now     Anchor datetime for evaluation (tests). Default: "now" in site tz.
     *     @var DateTimeZone       $tz      Timezone override. Default: site timezone.
     * }
     * @return array{start: DateTimeImmutable|null, end: DateTimeImmutable|null}
     // OR
     * @return array{start:string,end:string}
     */
    public static function resolve(string|array $scope, array $options = []): array
    {
        // Determine the Mode -- DATE or DATETIME -- this controls how scope boundaries are normalized
        /*
        DATE (default): day-granularity. Ranges are snapped to day edges in the site TZ
        e.g. today → 00:00:00 … 23:59:59, this_week → start-of-week 00:00:00 … end-of-week 23:59:59.

        DATETIME: time-granularity. You don’t coerce to day edges; ranges can be hour/minute precise
        e.g. last_24_hours → now-24h … now, this_hour → HH:00:00 … HH:59:59.
        */
        $mode = strtoupper((string)($options['mode'] ?? 'DATE'));
        if ($mode !== 'DATE' && $mode !== 'DATETIME') {
            throw new InvalidArgumentException('ScopedDateResolver: options.mode must be DATE or DATETIME.');
        }

        // Resolve tz + now
        $tzOpt = $options['tz'] ?? null;

        if ($tzOpt instanceof DateTimeZone) {
            $tz = $tzOpt;
        } elseif (is_string($tzOpt) && $tzOpt !== '') {
            try {
                $tz = new DateTimeZone($tzOpt);
            } catch (Exception $e) {
                $tz = DateHelper::siteTimezone();
            }
        } else {
            $tz = DateHelper::siteTimezone();
        }

        /** Now (immutable + normalized to site tz) */
        $provided = $options['now'] ?? null;

        $now = ($provided instanceof DateTimeInterface)
            ? DateTimeImmutable::createFromInterface($provided)->setTimezone($tz)
            : new DateTimeImmutable('now', $tz);

        //
        $start = null; $end = null; $explicit = false;

        // ---- Array scope (explicit range) handling ---------------------------------
        if (is_array($scope)) {
            $sIn = $scope['start'] ?? $scope['startDate'] ?? null;
            $eIn = $scope['end']   ?? $scope['endDate']   ?? null;

            if ($sIn instanceof DateTimeInterface) {
                $start = DateTimeImmutable::createFromInterface($sIn)->setTimezone($tz);
            } elseif (is_string($sIn) && $sIn !== '') {
                $start = DateHelper::parseFlexibleDate($sIn, true)->setTimezone($tz);
            } elseif (is_string($scope['date'] ?? '') && $eIn === null) {
                $start = DateHelper::parseFlexibleDate((string)$scope['date'], true)->setTimezone($tz);
            }

            if ($eIn instanceof DateTimeInterface) {
                $end = DateTimeImmutable::createFromInterface($eIn)->setTimezone($tz);
            } elseif (is_string($eIn) && $eIn !== '') {
                $end = DateHelper::parseFlexibleDate($eIn, true)->setTimezone($tz);
            } elseif (is_string($scope['range'] ?? '') && strpos((string)$scope['range'], ',') !== false) {
                [$a, $b] = array_map('trim', explode(',', (string)$scope['range'], 2));
                $start = DateHelper::parseFlexibleDate($a, true)->setTimezone($tz);
                $end   = DateHelper::parseFlexibleDate($b, true)->setTimezone($tz);
            }

            $explicit = true; // skip resolver dispatch & cache; fall through to rounding return
        }

        // ---- Request-scoped memoization (string scopes only) -----------------------
        if (is_string($scope)) {
            static $cache = [];
            $cacheKey = self::buildCacheKey($scope, $options, $tz, $now);
            if (empty($options['no_cache']) && isset($cache[$cacheKey])) {
                return $cache[$cacheKey];
            }
        }

        // Get resolvers (defaults + filters)
        $scopeResolvers = self::registeredScopes($now, $options);

        // Request-scoped memoization (skip if explicitly disabled)
        static $cache = [];
        $cacheKey = self::buildCacheKey(is_string($scope) ? $scope : 'custom', $options, $tz, $now);
        if (empty($options['no_cache']) && isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        // 4) Special case: "easter 2025" style scopes.. TBD: add additional special scopes? (string scopes only)
        if (is_string($scope) && preg_match('/^easter_(\d{4})$/', Text::snake($scope), $m)) { //'/^easter\s+(\d{4})$/i'
            $y = (int)$m[1];
            $e = DateHelper::calculateEasterDate($y, $tz);
            $start = $e->setTime(0, 0, 0);
            $end = $e->setTime(23, 59, 59);
        }

        // 5) Dispatch to the resolver for the requested scope. If invalid, default to 'today'
        $key      = Text::snake($scope);               // e.g. "This Week" → "this_week"
        $resolver = $scopeResolvers[$key] ?? $scopeResolvers['today'] ?? null;

        // These closures take no arguments; they already captured $now/$options via `use (...)`
        $range = $resolver ? $resolver() : ['start' => null, 'end' => null]; //$range = (array)call_user_func($resolvers[$key], $options);

        $start = $range['start'] ?? null;
        $end   = $range['end']   ?? null;

        // 6) Mode + inclusive-end flags (default DATE + inclusive end-of-day)
        $inclusiveEnd = array_key_exists('inclusive_end', $options) ? (bool)$options['inclusive_end'] : true;
        // In DATE mode, snap to day edges; in DATETIME mode, leave times as-is.
        if ($mode === 'DATE') {
            if ($start) { $start = $start->setTime(0, 0, 0); }
            if ($end && $inclusiveEnd) { $end = $end->setTime(23, 59, 59); } //$end = DateTimeImmutable::createFromInterface($end)->setTime(23, 59, 59);
        }

        $result = [
            'start' => $start instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($start) : null,
            'end'   => $end   instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($end)   : null,
        ];

        if (empty($options['no_cache'])) {
            $cache[$cacheKey] = $result;
        }

        return $result;

    }

    // ----- Internals -----------------------------------------------------

    /**
     * Merge default scope resolvers with site-registered overrides.
     *
     * @return array<string, callable(): array{start: DateTimeImmutable|null, end: DateTimeImmutable|null}>
     */
    private static function registeredScopes( DateTimeImmutable $now, array $options ): array
    {
        $scopeResolvers = self::defaultScopes($now, $options); // Note that the values are callables (functions that resolve a scope to a [start,end] range), not static “scope data.”

        if (function_exists('apply_filters')) {
            // Allow external code to add/override resolvers.
            /**
             * Filter to add or override named date scopes.
             *
             * Each entry should be a callable with signature:
             *   fn(DateTimeImmutable $now, DateTimeZone $tz): array{0:DateTimeImmutable,1:DateTimeImmutable}
             *
             * Example:
             *   add_filter('whx4_scopes_register', function($scopes) {
             *       $scopes['next_90_days'] = function($now) {
             *           $start = self::atStartOfDay($now);
             *           $end   = $start->add(new DateInterval('P90D'));
             *           return [$start, $end];
             *       };
             *       return $scopes;
             *   });
             */
            // Pass $now and $options for context if users want them.
            $filtered = apply_filters('whx4_scopes_register', $scopeResolvers, $now, $options);

            if (is_array($filtered)) {
                // Keep only callables; preserve keys.
                $scopeResolvers = array_filter($filtered, 'is_callable');

                // Ensure we always have a valid 'today' fallback.
                if (!isset($scopeResolvers['today']) || !is_callable($scopeResolvers['today'])) {
                    $defaults = self::defaultScopes($now, $options);
                    if (isset($defaults['today']) && is_callable($defaults['today'])) {
                        $scopeResolvers['today'] = $defaults['today'];
                    }
                }
            }
        }

        // Safety net: if everything was filtered out, restore defaults.
        if (empty($scopeResolvers)) {
            $scopeResolvers = self::defaultScopes($now, $options);
        }

        return $scopeResolvers;
    }

    /**
     * Default named scopes.
     *
     * @return array<string, callable(): array{start: DateTimeImmutable|null, end: DateTimeImmutable|null}>
     */
    private static function defaultScopes( DateTimeImmutable $now, array $options ): array
    {
        $Y = (int)$now->format('Y');
        $M = (int)$now->format('n');

        // Helpers to avoid repetition
        $dti = static function(int $y, int $m, int $d, int $H = 0, int $i = 0, int $s = 0) use ($now): DateTimeImmutable {
            return $now->setDate($y, $m, $d)->setTime($H, $i, $s);
        };

        //
        $dayRange = static function(DateTimeImmutable $ref): array {
            $start = $ref->setTime(0, 0, 0);
            $end = $ref->setTime(23, 59, 59);
            return compact('start', 'end');
        };

        $startOfWeek = function_exists('get_option') ? (int) get_option('start_of_week', 1) : 1;
        $weekRange = static function(DateTimeImmutable $ref) use ($startOfWeek): array {
            //$start = $ref->modify('monday this week')->setTime(0, 0, 0);
            //$end = $ref->modify('sunday this week')->setTime(23, 59, 59);
            if ($startOfWeek === 0) { // Sunday start
                $w = (int) $ref->format('w'); // 0 (Sun) .. 6 (Sat)
                $start = $ref->modify('-' . $w . ' days')->setTime(0, 0, 0);
            } else { // Monday start (default if not 0)
                $w = (int) $ref->format('N'); // 1 (Mon) .. 7 (Sun)
                $start = $ref->modify('-' . ($w - 1) . ' days')->setTime(0, 0, 0);
            }

            $end = $start->modify('+6 days')->setTime(23, 59, 59);
            return compact('start', 'end');
        };

        $monthRange = static function(int $y, int $m) use ($dti): array {
            $start = $dti($y, $m, 1, 0, 0, 0);
            $end = $start->modify('last day of this month')->setTime(23, 59, 59);
            return compact('start', 'end');
        };

        $yearRange = static function(int $y) use ($dti): array {
            $start = $dti($y, 1, 1, 0, 0, 0);
            $end = $dti($y, 12, 31, 23, 59, 59);
            return compact('start', 'end');
        };

        $seasonRange = static function(int $y, int $m) use ($dti): array {
            if ($m >= 9) {
                $start = $dti($y, 9, 1, 0, 0, 0);
                $end = $dti($y + 1, 5, 31, 23, 59, 59);
            } else {
                $start = $dti($y - 1, 9, 1, 0, 0, 0);
                $end = $dti($y, 5, 31, 23, 59, 59);
            }
            return compact('start', 'end');
        };

        return [
            // Days
            'today' => function() use ($now, $dayRange) {
                return $dayRange($now);
            },
            'yesterday' => function() use ($now, $dayRange) {
                return $dayRange($now->modify('-1 day'));
            },
            'tomorrow' => function() use ($now, $dayRange) {
                return $dayRange($now->modify('+1 day'));
            },

            // Weeks
            'this_week' => function() use ($now, $weekRange) {
                return $weekRange($now);
            },
            'last_week' => function() use ($now, $weekRange) {
                return $weekRange($now->modify('-1 week'));
            },
            'next_week' => function() use ($now, $weekRange) {
                return $weekRange($now->modify('+1 week'));
            },

            // Months
            'this_month' => function() use ($Y, $M, $monthRange) {
                return $monthRange($Y, $M);
            },
            'last_month' => function() use ($Y, $M, $monthRange) {
                $y = $Y;
                $m = $M - 1;
                if ($m < 1) { $m = 12; $y -= 1; }
                return $monthRange($y, $m);
            },
            'next_month' => function() use ($Y, $M, $monthRange) {
                $y = $Y;
                $m = $M + 1;
                if ($m > 12) { $m = 1; $y += 1; }
                return $monthRange($y, $m);
            },
            // parameterized month via options['year'], options['month']
            'month' => function() use ($options, $Y, $M, $monthRange) {
                $year = isset($options['year']) ? (int)$options['year'] : $Y;
                $mon = isset($options['month']) ? max(1, min(12, (int)$options['month'])) : $M;
                return $monthRange($year, $mon);
            },

            // Years
            'this_year' => function() use ($Y, $yearRange) {
                return $yearRange($Y);
            },
            'last_year' => function() use ($Y, $yearRange) {
                return $yearRange($Y - 1);
            },
            'next_year' => function() use ($Y, $yearRange) {
                return $yearRange($Y + 1);
            },

            // Seasons
            // The 'season' as defined above runs from Sep 1 -> May 31
            'this_season' => function() use ($Y, $M, $seasonRange) {
                return $seasonRange($Y, $M); // Sep 1 → May 31 spanning as needed
            },
            'next_season' => function() use ($Y, $M, $seasonRange) {
                $y = ($M >= 9) ? $Y + 1 : $Y; // next season’s start year
                return $seasonRange($y, 9);   // force Sep branch
            },

            // Open-ended examples
            'ytd' => function() use ($Y, $dti) { // year-to-date, aka 'since_start_of_year'
                $start = $dti($Y, 1, 1, 0, 0, 0);
                return ['start' => $start, 'end' => null];
            },
            'until_today' => function() use ($now) {
                $end = $now->setTime(23, 59, 59);
                return ['start' => null, 'end' => $end];
            },
        ];
    }

    /*
    // Old version:
    private static function cacheKey(string|array $scope, string $mode, DateTimeZone $tz, DateTimeImmutable $now): string
    {
        return md5(json_encode([$scope, $mode, $tz->getName(), $now->format('c')], JSON_THROW_ON_ERROR));
    }*/

    /**
     * Build a stable cache key for scope resolution.
     *
     * Components (ordered):
     *  scopeKey | mode | inclusiveEnd | tzName | startOfWeek | [anchor] | [year=YYYY] | [month=MM] | [cache_key]
     */
    private static function buildCacheKey(string $scope, array $options, DateTimeZone $tz, DateTimeImmutable $now): string
    {
        $scopeKey     = Text::snake($scope);
        $mode         = strtoupper((string)($options['mode'] ?? 'DATE'));   // DATE | DATETIME
        $inclusiveEnd = (int)($options['inclusive_end'] ?? 1);
        $startOfWeek  = function_exists('get_option') ? (int)get_option('start_of_week', 1) : 1;

        // Determine if this scope depends on "now"
        $usesNow = true;
        if ($scopeKey === 'month' && isset($options['year'], $options['month'])) {
            $usesNow = false;
        }
        if (preg_match('/^easter_\d{4}$/', $scopeKey)) {
            $usesNow = false;
        }

        $segments = [
            $scopeKey,
            $mode,
            (string)$inclusiveEnd,
            $tz->getName(),
            (string)$startOfWeek,
        ];

        if ($usesNow) {
            $anchor = $mode === 'DATE' ? $now->format('Y-m-d') : $now->format('Y-m-d H:i');
            $segments[] = $anchor;
        }

        if (array_key_exists('year', $options)) {
            $segments[] = 'year=' . (int)$options['year'];
        }
        if (array_key_exists('month', $options)) {
            $m = (int)$options['month'];
            $segments[] = 'month=' . sprintf('%02d', max(1, min(12, $m)));
        }

        if (!empty($options['cache_key']) && is_string($options['cache_key'])) {
            $segments[] = $options['cache_key'];
        }

        return implode('|', $segments);
    }

}
