<?php

namespace atc\WHx4\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use atc\WHx4\Query\ScopedDateResolver;

class DateHelper
{
    /**
     * Normalize date input to standardized Y-m-d values or DateTimeImmutable objects.
     *
     * Delegates all scope logic to ScopedDateResolver.
     *
     * Args:
     * - scope: string|null -- Optional keyword like 'this_month' or 'Easter 2025'
     * - date: string|DateTimeInterface|null (single date or "start,end") -- (?) A date string, range, or DateTime object
     * - year: int|null (used when month is present) -- ??? WIP -- Optional year fallback
     * - month: int|string|null (1-12 or month name/abbr — will be normalized) -- Optional month fallback
     * - asDateObjects: bool (default false) -- If true, returns DateTimeImmutable objects
     *
     * Returns:
     * - string "Y-m-d"
     * - DateTimeImmutable
     * - array{startDate: string, endDate: string} or array{startDate: DateTimeImmutable, endDate: DateTimeImmutable}
     * i.e. @return array|DateTimeImmutable|string     Array with 'startDate' and 'endDate' or single string if same
     */
    public static function normalizeDateInput( array $args = [] ): array|DateTimeImmutable|string
    {
        $defaults = [
            'scope' => null,
            'date' => null,
            'year' => null,
            'month' => null,
            'asDateObjects' => false,
        ];
        $args = function_exists('wp_parse_args') ? wp_parse_args($args, $defaults) : array_merge($defaults, $args);

        $scope = $args['scope'];
        $date = $args['date'];
        $year = $args['year'];
        $month = $args['month'];
        $asObjects = (bool)$args['asDateObjects'];

        // 1) Scope wins — centralize in ScopedDateResolver
        if (is_string($scope) && $scope !== '') {
            $resolved = ScopedDateResolver::resolve($scope, [
                'year' => $year,
                'month' => is_string($month) ? self::normalizeMonthToInt($month) : $month,
            ]);
            //
            $start = $resolved['start'];
            $end = $resolved['end'];
        }

        // 2) Explicit date(s)
        if ( $date instanceof DateTimeInterface ) {
            $d = DateTimeImmutable::createFromInterface($date);
            return $asObjects ? $d : $d->format('Y-m-d');
        }
        // date string, representing either a single date or date range
        if (is_string($date) && $date !== '') {
            if (strpos($date, ',') !== false) { // date range, comma-separated
                [$rawStart, $rawEnd] = array_map('trim', explode(',', $date, 2));
                $start = self::parseFlexibleDate($rawStart, true);
                $end = self::parseFlexibleDate($rawEnd, true);
            } else {
                return self::parseFlexibleDate($date, $asObjects);
            }
        }

        // 3) Month/year helper (no scope, no explicit date)
        if ($month !== null) {
            //$month = str_pad( (string)(int) $month, 2, '0', STR_PAD_LEFT );
            $m = is_string($month) ? self::normalizeMonthToInt($month) : (int)$month;
            if ($m >= 1 && $m <= 12) {
                $y = $year !== null ? (int)$year : (int)(new DateTimeImmutable())->format('Y');
                $start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-01 00:00:00', $y, $m)); // hour/minute/second because: XXX //$start = DateTimeImmutable::createFromFormat( 'Y-m-d', "{$year}-{$month}-01" );
                $end = $start->modify('last day of this month')->setTime(23, 59, 59); //$end = $start->modify( 'last day of this month' );
            }
        }

        if ( $start && $end ) {
            // Both start and end are non-null => return an array
            return $asObjects
                ? [ 'startDate' => $start, 'endDate' => $end ]
                : [ 'startDate' => $start->format( 'Y-m-d' ), 'endDate' => $end->format( 'Y-m-d' ) ];
        } elseif ( $start ) {
            // Only start is set => return single date object or string
            return $asObjects ? $start : $start->format( 'Y-m-d' );
        } elseif ( $end ) {
            // Only end is set => return single date object or string
            return $asObjects ? $end : $end->format( 'Y-m-d' );
        }

        // 4) Failsafe: return today's date
        $today = new DateTimeImmutable();
        return $asObjects ? $today : $today->format('Y-m-d');
        //return $asDateObjects ? $now : $now->format( 'Y-m-d' ); // failsafe
    }

    /**
     * Parse a flexible natural-language date string.
     *
     * @param string $input
     * @param bool $asDateObject
     * @return string|DateTimeImmutable
     */
    public static function parseFlexibleDate( string $input, bool $asDateObject = false ): string|DateTimeImmutable
    {
        try {
            $dt = new DateTimeImmutable( $input );
            return $asDateObject ? $dt : $dt->format( 'Y-m-d' );
        } catch ( Exception $e ) {
            return $asDateObject ? new DateTimeImmutable() : '';
        }
    }

    public static function normalizeMonthToInt( string $month ): ?int
    {
        $month = strtolower( trim( $month ) );

        $map = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];

        return $map[ $month ] ?? null;
    }

    /**
     * Combine a date and optional time string into a DateTimeImmutable object.
     *
     * @param string|null $date A date string (e.g. '2025-06-21')
     * @param string|null $time A time string (e.g. '14:30') — optional
     * @return \DateTimeImmutable|null
     */
    public static function combineDateAndTime( ?string $date, ?string $time = null ): ?DateTimeImmutable
    {
        if (!$date) {
            return null;
        }

        $datetimeString = trim($date . ' ' . ($time ?? ''));

        try {
            return new DateTimeImmutable( $datetimeString );
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Calculate Easter Sunday for a given year (kept for convenience; ScopedDateResolver uses its own internal helper).
     *
     * @param int $year
     * @return DateTimeImmutable
     */
    public static function calculateEasterDate( int $year ): DateTimeImmutable
    {
        $timestamp = easter_date( $year );
        return ( new DateTimeImmutable() )->setTimestamp( $timestamp );
    }
}
