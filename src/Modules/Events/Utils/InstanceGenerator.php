<?php

namespace atc\WHx4\Modules\Events\Utils;

use RRule\RRule; // RRule\RRule accepts any DateTimeInterface
use DateTimeInterface; // See chat: "An interface: implemented by both DateTime and DateTimeImmutable"
//use DateTimeImmutable; // See chat: "A concrete class: creates new objects when modified"
use atc\WHx4\Modules\Events\EventOverrides;

class InstanceGenerator
{
    /**
     * Generate instance dates based on RRULE, exclusions, and overrides.
     *
     * @param  DateTimeInterface  $start
     * @param  string             $rrule
     * @param  array              $exdates  ISO-formatted datetimes to exclude
     * @param  array              $overrides keyed by ISO datetime => DateTimeInterface
     * @param  int                $limit
     * @param  DateTimeInterface|null $until
     * @return DateTimeInterface[]
     */
    public static function generateInstanceDates(
        DateTimeInterface $start,
        string $rrule,
        array $exdates = [],
        array $overrides = [],
        int $limit = 100,
        ?DateTimeInterface $until = null
    ): array {
        if ( ! $start || ! $rrule ) {
            return [];
        }

        $rule = new RRule([
            'DTSTART' => $start,
            'RRULE'   => $rrule,
        ]);

        $results = [];

        foreach ( $rule as $dt ) {
            $iso = $dt->format( 'Y-m-d\TH:i:s' );

            if ( $until && $dt > $until ) {
                break;
            }

            if ( in_array( $iso, $exdates, true ) ) {
                continue;
            }

            if ( isset( $overrides[ $iso ] ) && $overrides[ $iso ] instanceof DateTimeInterface ) {
                $dt = $overrides[ $iso ];
            }

            $results[] = $dt;

            if ( count( $results ) >= $limit ) {
                break;
            }
        }

        return $results;
    }

    /**
     * Generate instance dates using a post ID (convenience wrapper).
     *
     * @param  int                $post_id
     * @param  int                $limit
     * @param  DateTimeInterface|null $until
     * @return DateTimeInterface[]
     */
    public static function fromPostId( int $post_id, int $limit = 100, ?DateTimeInterface $until = null ): array
    {
        $start = get_field( 'whx4_events_start_date', $post_id );
        $rrule = get_field( 'whx4_events_rrule', $post_id );

        if ( ! $start || ! $rrule ) {
            return [];
        }

        $exdates = get_post_meta( $post_id, 'whx4_events_excluded_dates', true ) ?: [];
        if ( !is_array( $exdates ) ) {
            return [];
        }
        $exdates = array_map(
            fn( $date ) => is_string( $date ) ? ( new \DateTime( $date ) )->format( 'Y-m-d\TH:i:s' ) : $date,
            $exdates
        );

        $overrides = EventOverrides::getOverrideDates( $post_id );

        return self::generateInstanceDates(
            $start,
            $rrule,
            $exdates,
            $overrides,
            $limit,
            $until
        );
    }
}
