<?php

namespace atc\WHx4\Utils\Recurrence;

use DateTimeImmutable;
use RRule\RRule;
use WP_Post;

class InstanceGenerator
{
    protected WP_Post $event;

    public function __construct( WP_Post $event )
    {
        $this->event = $event;
    }

    public function getStart(): ?DateTimeImmutable
    {
        $raw = get_post_meta( $this->event->ID, 'whx4_events_start_datetime', true );
        return $raw ? new DateTimeImmutable( $raw ) : null;
    }

    public function getRule(): ?string
    {
        return get_post_meta( $this->event->ID, 'whx4_events_recurrence_rule', true );
    }

    public function getExclusions(): array
    {
        $rows = get_field( 'whx4_events_excluded_datetimes', $this->event->ID );
        $dates = [];

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                if ( !empty( $row['datetime'] ) ) {
                    $dates[] = ( new DateTimeImmutable( $row['datetime'] ) )->format( 'Y-m-d\TH:i:s' );
                }
            }
        }

        return $dates;
    }

    public function getOverrides(): array
    {
        $rows = get_field( 'whx4_events_instance_overrides', $this->event->ID );
        $map = [];

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                if ( !empty( $row['original'] ) ) {
                    $key = ( new DateTimeImmutable( $row['original'] ) )->format( 'Y-m-d\TH:i:s' );

                    $map[ $key ] = [
                        'replacement' => !empty( $row['replacement'] ) ? new DateTimeImmutable( $row['replacement'] ) : null,
                        'note'        => $row['note'] ?? '',
                    ];
                }
            }
        }

        return $map;
    }

    public function generateInstances( int $limit = 100, ?\DateTimeInterface $until = null ): array
    {
        $start     = $this->getStart();
        $rrule     = $this->getRule();
        $exdates   = $this->getExclusions();
        $overrides = $this->getOverrides();

        if ( !$start || !$rrule ) {
            return [];
        }

        $rule = new RRule([
            'DTSTART' => $start,
            'RRULE'   => $rrule,
        ]);

        $instances = [];

        foreach ( $rule as $dt ) {
            $iso = $dt->format( 'Y-m-d\TH:i:s' );

            if ( $until && $dt > $until ) {
                break;
            }

            if ( in_array( $iso, $exdates, true ) ) {
                continue;
            }

            if ( isset( $overrides[ $iso ] ) ) {
                $override = $overrides[ $iso ];
                if ( $override['replacement'] instanceof \DateTimeInterface ) {
                    $dt = $override['replacement'];
                }
            }

            $instances[] = $dt;

            if ( count( $instances ) >= $limit ) {
                break;
            }
        }

        return $instances;
    }
}

/* Example Usage:

use smith\Rex\Modules\Events\Recurrence\InstanceGenerator;

$generator = new InstanceGenerator( $event_post );
$occurrences = $generator->generateInstances( 50 );

foreach ( $occurrences as $occurrence ) {
    echo $occurrence->format( 'Y-m-d H:i' );
}
*/
