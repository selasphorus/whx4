<?php

namespace atc\WHx4\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/*
Anywhere inside the Rex plugin or dependent modules:

php
Copy
Edit
use smith\Rex\Utils\DateHelper;

$normalized = DateHelper::normalize_date_input( 'this_week' );
*/

class DateHelper {

    /**
     * Normalize date input to standardized Y-m-d values or DateTimeImmutable objects.
     *
     * @param string|null                  $scope         Optional keyword like 'this_month' or 'Easter 2025'
     * @param string|DateTimeInterface|null $date         A date string, range, or DateTime object
     * @param int|null                     $year          Optional year fallback
     * @param int|string|null              $month         Optional month fallback
     * @param bool                         $asDateObjects If true, returns DateTimeImmutable objects
     * @return array|DateTimeImmutable|string     Array with 'startDate' and 'endDate' or single string if same
     */
    public static function normalizeDateInput( array $args = [] ): array|DateTimeImmutable|string
    {
        $args = wp_parse_args( $args, [
            'scope'         => null,
            'date'          => null,
            'year'          => null,
            'month'         => null,
            'asDateObjects' => false,
        ] );
        extract( $args ); // TODO: maybe do this manually instead for safety?

        $now = new DateTimeImmutable();
        $start = $now; // default to now
        $end = null;

        if ( is_string( $scope ) ) {
            $scopeKey = strtolower( str_replace( ' ', '_', $scope ) );

            switch ( $scopeKey ) {
                case 'today':
                    $start = $now;

                case 'this_week':
                    $start = $now->modify( 'monday this week' );
                    $end   = $now->modify( 'sunday this week' );

                case 'this_month':
                    $start = $now->modify( 'first day of this month' );
                    $end   = $now->modify( 'last day of this month' );

                case 'last_year':
                    $start = new DateTimeImmutable( 'first day of January last year' );
                    $end   = new DateTimeImmutable( 'last day of December last year' );

                case 'next_year':
                    $start = new DateTimeImmutable( 'first day of January next year' );
                    $end   = new DateTimeImmutable( 'last day of December next year' );

                case 'this_season':
                    $monthNow = (int) $now->format( 'n' );
                    $yearNow  = (int) $now->format( 'Y' );

                    if ( $monthNow >= 9 ) {
                        $start = new DateTimeImmutable( "$yearNow-09-01" );
                        $end   = new DateTimeImmutable( ($yearNow + 1) . "-05-31" );
                    } else {
                        $start = new DateTimeImmutable( ($yearNow - 1) . "-09-01" );
                        $end   = new DateTimeImmutable( "$yearNow-05-31" );
                    }
            }

            // Easter YYYY support
            if ( preg_match( '/^easter\s+(\d{4})$/i', $scope, $matches ) ) {
                $easter = self::calculateEasterDate( (int) $matches[1] );
                $start = $easter;
            }
        }

        if ( $date instanceof DateTimeInterface ) {
            return $asDateObjects ? DateTimeImmutable::createFromInterface( $date ) : $date->format( 'Y-m-d' );
        }

        /*
        // Date range in format "YYYY-mm-dd, YYYY-mm-dd"? Then set start, end dates
        //if ( is_string( $date ) && strpos( $date, ',' ) !== false ) {
        if ( preg_match( '/^\d{4}-\d{2}-\d{2},\s?\d{4}-\d{2}-\d{2}$/', $date ) ) {
            [ $raw_start, $raw_end ] = explode( ',', $date, 2 );
            $start = parseFlexibleDate( trim( $raw_start ) );
            $end   = parseFlexibleDate( trim( $raw_end ) );
            return [ 'startDate' => $start, 'endDate' => $end ];
        }
        */

        /// ??? WIP ???
        if ( is_string( $date ) ) {
            if ( strpos( $date, ',' ) !== false ) {
                [ $rawStart, $rawEnd ] = explode( ',', $date, 2 );
                $start = self::parseFlexibleDate( trim( $rawStart ), $asDateObjects );
                $end   = self::parseFlexibleDate( trim( $rawEnd ), $asDateObjects );
                //return [ 'startDate' => $start, 'endDate' => $end ]; // ???
            } else {
                return self::parseFlexibleDate( $date, $asDateObjects );
            }
        }

        if ( $month ) {
            $month = str_pad( (string)(int) $month, 2, '0', STR_PAD_LEFT );
            $year  = $year ?? (int) $now->format( 'Y' );
            $start = DateTimeImmutable::createFromFormat( 'Y-m-d', "{$year}-{$month}-01" );
            $end   = $start->modify( 'last day of this month' );
        }

        if ( $start && $end ) {
            // Both start and end are non-null => return an array
            return $asDateObjects
                ? [ 'startDate' => $start, 'endDate' => $end ]
                : [ 'startDate' => $start->format( 'Y-m-d' ), 'endDate' => $end->format( 'Y-m-d' ) ];
        } else if ( $start ) {
            // Only start is set => return single date object or string
            return $asDateObjects ? $start : $start->format( 'Y-m-d' );
        }

        return $asDateObjects ? $now : $now->format( 'Y-m-d' ); // failsafe
    }

    /**
     * Parse a flexible natural-language date string.
     *
     * @param string $input
     * @param bool   $asDateObject
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
            'jan' => 1, 'january'   => 1,
            'feb' => 2, 'february'  => 2,
            'mar' => 3, 'march'     => 3,
            'apr' => 4, 'april'     => 4,
            'may' => 5,
            'jun' => 6, 'june'      => 6,
            'jul' => 7, 'july'      => 7,
            'aug' => 8, 'august'    => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october'  => 10,
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
    public static function combineDateAndTime( ?string $date, ?string $time = null ): ?\DateTimeImmutable
    {
        if ( ! $date ) {
            return null;
        }

        $datetime_string = trim( $date . ' ' . ( $time ?? '' ) );

        try {
            return new \DateTimeImmutable( $datetime_string );
        } catch ( \Exception $e ) {
            return null;
        }
    }

    /**
     * Calculate Easter Sunday for a given year.
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
