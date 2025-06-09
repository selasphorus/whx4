<?php
namespace atc\WHx4\Modules\Events\Utils;

use WP_Post;
use atc\WHx4\Modules\Events\Utils\InstanceGenerator;

class EventsHelper
{
    public static function getFlattenedInstances( array $args = [] ): array
    {
        $defaults = [
            'post_type'   => 'rex_event',
            'post_status' => 'publish',
            'numberposts' => -1,
            'date_range'  => null, // optional ['start' => DateTime, 'end' => DateTime]
            'limit'       => 100,
        ];

        $args = wp_parse_args( $args, $defaults );

        $posts = get_posts( $args );
        $results = [];

        foreach ( $posts as $post ) {
            $generator = new InstanceGenerator( $post );

            if ( $generator->getRule() ) {
                $until = $args['date_range']['end'] ?? null;
                $instances = $generator->generateInstances( $args['limit'], $until );

                foreach ( $instances as $dt ) {
                    if ( $args['date_range'] ) {
                        $start = $args['date_range']['start'];
                        $end = $args['date_range']['end'];

                        if ( $dt < $start || $dt > $end ) {
                            continue;
                        }
                    }

                    $results[] = [
                        'post'     => $post,
                        'datetime' => $dt,
                        'is_virtual' => true,
                    ];
                }
            } else {
                $startRaw = get_post_meta( $post->ID, 'rex_events_start_datetime', true );
                if ( $startRaw ) {
                    $start = new \DateTimeImmutable( $startRaw );

                    if ( $args['date_range'] ) {
                        $range = $args['date_range'];
                        if ( $start < $range['start'] || $start > $range['end'] ) {
                            continue;
                        }
                    }

                    $results[] = [
                        'post'     => $post,
                        'datetime' => $start,
                        'is_virtual' => false,
                    ];
                }
            }
        }

        usort( $results, fn( $a, $b ) => $a['datetime'] <=> $b['datetime'] );

        return $results;
    }
}
