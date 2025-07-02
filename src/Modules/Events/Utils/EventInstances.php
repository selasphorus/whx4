<?php

namespace atc\WHx4\Modules\Events\Utils;

use WP_Post;
use WP_Query;
use atc\WHx4\Utils\DateHelper;
use atc\WHx4\Utils\RepeaterChangeDetector;
use atc\WHx4\Helpers\PluginPaths;

class EventInstances
{
    public static function register(): void
    {
        add_action( 'add_meta_boxes', [self::class, 'addMetaBox'] );
        add_action( 'edit_form_top', [self::class, 'maybeAddDetachedNotice'] );
        add_action( 'admin_notices', [self::class, 'maybeShowDetachedCleanupNotice'] );
        add_action( 'admin_init', [self::class, 'handleDetachedCleanupRequest'] );
        //add_action( 'acf/save_post', [self::class, 'handleExcludedDateRemovals'], 20 );
        add_action( 'admin_enqueue_scripts', [self::class, 'enqueueAdminAssets'] );
        //add_action( 'admin_init', [self::class, 'handleCreateRequest'] );
        add_action( 'wp_ajax_whx4_create_replacement', [ self::class, 'handleCreateReplacement' ] );
        //add_action( 'wp_ajax_whx4_check_replacement', [ \smith\Rex\Events\Admin\EventInstances::class, 'ajaxCheckReplacement' ] );
    }

    public static function addMetaBox(): void
    {
        add_meta_box(
            'whx4_event_exclusions_box',
            'Excluded Dates & Overrides',
            [self::class, 'renderMetaBox'],
            'whx4_event', //'event',
            'side'
        );
    }

    public static function renderMetaBox( WP_Post $post ): void
    {
        $post_id = $post->ID;
        $instances = InstanceGenerator::fromPostId( $post->ID, 50, true );
        //$dates = InstanceGenerator::generateInstanceDates( $post_id );
        //echo "instances: <pre>" . print_r($instances, true) . "</pre>";

        $excluded = get_post_meta( $post_id, 'whx4_events_excluded_dates', true ) ?: [];
        $excluded = maybe_unserialize($excluded);
        //echo "excluded: <pre>" . print_r($excluded, true) . "</pre>";

        echo '<div class="whx4-event-instances-columns">';

        foreach ( $instances as $date ) {
            $date_str = $date->format( 'Y-m-d' );
            $label = $date->format( 'l, M d, Y' );

            //
            $is_excluded = in_array( $date_str, $excluded );
            //
            $replacement_id = self::getDetachedPostId( $post_id, $date_str );

            echo '<div class="whx4-instance-block">';
            echo '<div class="whx4-instance-date">' . esc_html( $label ) . '</div>';
            echo '<div class="whx4-instance-actions">';

            if ( $replacement_id ) {
                echo '<a href="' . esc_url( get_edit_post_link( $replacement_id ) ) . '" target="_blank" class="button">Edit replacement</a>';
            } elseif ( $is_excluded ) {
                echo '<span class="icon-button disabled"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/excluded.png" alt="Excluded"></span>&nbsp;';
                //echo '<span class="button disabled">Excluded</span> ';
                echo '<button type="button" class="button icon-button whx4-unexclude-date" data-action="unexclude_date" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/unexclude.png" alt="Exclude"></button>';
            } else {
                echo '<button type="button" class="button icon-button whx4-exclude-date" data-action="exclude_date" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/exclude.png" alt="Exclude"></button> ';
                echo '<button type="button" class="button icon-button whx4-create-replacement" data-action="create_replacement" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/detach.png" alt="Create Replacement Event"></button>';
            }

            echo '</div></div>'; // close .whx4-instance-actions, .whx4-instance-block
        }

        echo '</div>'; // close .whx4-event-instances-columns


        /*echo '<div class="whx4-event-instances-grid">';

        foreach ( $instances as $date ) {
            $date_str = $date->format( 'Y-m-d' );
            $label = $date->format( 'M j, Y' );

            $is_excluded = is_array( $excluded ) && in_array( $date_str, $excluded, true );
            $replacement_id = self::getDetachedPostId( $post_id, $date_str );
            echo '<div class="whx4-instance-cell">';
            echo '<div class="whx4-instance-date">' . esc_html( $label ) . '</div>';
            echo '<div class="whx4-instance-actions">';

            if ( $replacement_id ) {
                echo '<a href="' . esc_url( get_edit_post_link( $replacement_id ) ) . '" target="_blank" class="button">Edit replacement</a>';
            } elseif ( $is_excluded ) {
                echo '<span class="button disabled">Excluded</span> ';
                echo '<button type="button" class="button whx4-unexclude-date" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '">Un-exclude</button>';
            } else {
                echo '<button type="button" class="button whx4-exclude-date" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '">Exclude</button> ';
                echo '<button type="button" class="button whx4-create-replacement" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '">Create replacement</button>';
            }

            echo '</div></div>'; // close .whx4-instance-actions, .whx4-instance-cell
        }

        echo '</div>'; // close .whx4-event-instances-grid
        */
        /*echo '<table class="widefat">';
        echo '<thead><tr><th>Date</th><th>Actions</th></tr></thead><tbody>';
        foreach ( $instances as $date ) {
            $date_str = $date->format( 'Y-m-d' );
            $label = $date->format( 'M j, Y' );

            if ( is_array( $excluded ) ) { $is_excluded = in_array( $date_str, $excluded, true ); } else { $is_excluded = false; }
            $replacement_id = self::getDetachedPostId( $post_id, $date_str );

            echo '<tr>';
            echo '<td>' . esc_html( $label ) . '</td>';
            echo '<td>';

            if ( $replacement_id ) {
                echo '<a href="' . esc_url( get_edit_post_link( $replacement_id ) ) . '" target="_blank" class="button">Edit replacement</a>';
            } elseif ( $is_excluded ) {
                echo '<span class="button disabled">Excluded</span> ';
                echo '<button type="button" class="button whx4-unexclude-date" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '">Un-exclude</button>';
            } else {
                echo '<button type="button" class="button whx4-exclude-date" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '">Exclude</button> ';
                echo '<button type="button" class="button whx4-create-replacement" data-date="' . esc_attr( $date_str ) . '" data-post-id="' . esc_attr( $post_id ) . '">Create replacement</button>';
            }

            echo '</td></tr>';
        }
        echo '</tbody></table>';*/
    }

    // WIP 06-30-25 -- todo: compare/merge handleCreateReplacement with handleCreateRequest
    public static function handleCreateReplacement(): void
    {
        check_ajax_referer( 'whx4_events_nonce', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );
        $date = sanitize_text_field( $_POST['date'] ?? '' );

        if ( ! $post_id || ! $date ) {
            wp_send_json_error( [ 'message' => 'Missing data' ] );
        }

        // Create replacement if it doesn’t exist already
        $replacement_id = self::getDetachedPostId( $post_id, $date );
        if ( $replacement_id ) {
            $url = get_edit_post_link( $replacement_id );
            wp_send_json_success( [ 'exists' => true, 'edit_url' => $url ] );
        }

        $new_id = self::createDetachedReplacement( $post_id, $date );
        if ( is_wp_error( $new_id ) || ! $new_id ) {
            wp_send_json_error( [ 'message' => 'Failed to create replacement.' ] );
        }

        wp_send_json_success( [
            'exists'    => false,
            'created'   => true,
            'edit_url'  => get_edit_post_link( $new_id )
        ] );
    }

    public static function handleCreateRequest(): void
    {
        if (
            ! isset( $_GET['whx4_create_detached_event'], $_GET['event_id'], $_GET['date'], $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( $_GET['_wpnonce'], 'whx4_create_detached_event' )
        ) {
            return;
        }

        $event_id = (int) $_GET['event_id'];
        $date = sanitize_text_field( $_GET['date'] );

        if ( self::replacementExists( $event_id, $date ) ) {
            wp_safe_redirect( admin_url( 'edit.php?post_type=whx4_event' ) ); // eventually: post_type=event (after EM phase-out)
            exit;
        }

        $original = get_post( $event_id );
        if ( ! $original instanceof WP_Post || $original->post_type !== 'whx4_event' ) { // 'event'
            return;
        }

        $clone_id = wp_insert_post([
            'post_type' => 'whx4_event', //'event',
            'post_status' => 'draft',
            'post_title' => $original->post_title . ' (' . $date . ')',
            'post_content' => $original->post_content,
        ]);

        $meta = get_post_meta( $event_id );
        foreach ( $meta as $key => $values ) {
            if (
                str_starts_with( $key, 'whx4_events_rrule' )
                || str_starts_with( $key, 'whx4_events_recurrence' )
                || str_starts_with( $key, 'whx4_events_excluded_dates' )
                ) {
                continue;
            }
            /*if ( in_array( $key, [ 'whx4_events_rrule', 'whx4_events_excluded_dates' ], true ) ) {
                continue;
            }*/

            foreach ( $values as $val ) {
                update_post_meta( $clone_id, $key, maybe_unserialize( $val ) );
            }
        }

        // Update date to exclusion date
        update_post_meta( $clone_id, 'whx4_events_start_date', $date );

        // Mark this as a detached instance
        update_post_meta( $clone_id, 'whx4_events_detached_from', $event_id );
        update_post_meta( $clone_id, 'whx4_events_detached_date', $date );

        // Ensure it's not treated as recurring
        update_post_meta( $clone_id, 'whx4_events_is_recurring', 0 );

        wp_safe_redirect( get_edit_post_link( $clone_id, '' ) );
        exit;
    }

    protected static function replacementExists( int $parent_id, string $date ): bool
    {
        $query = new WP_Query([
            'post_type' => 'whx4_event', //'event',
            'post_status' => [ 'publish', 'draft', 'pending' ],
            'meta_query' => [
                [
                    'key' => 'whx4_events_detached_from',
                    'value' => $parent_id,
                ],
                [
                    'key' => 'whx4_events_detached_date',
                    'value' => $date,
                ],
            ],
            'fields' => 'ids',
        ]);

        return ! empty( $query->posts );
    }

    public static function maybeAddDetachedNotice( WP_Post $post ): void
    {
        if ( $post->post_type !== 'whx4_event' ) {
            return;
        }

        $original_id = get_post_meta( $post->ID, 'whx4_events_detached_from', true );

        if ( ! $original_id ) {
            return;
        }

        $url = get_edit_post_link( $original_id, '' );

        echo '<div class="notice notice-info"><p>';
        echo 'This is a <strong>replacement</strong> for a recurring event on <strong>' .
             esc_html( get_post_meta( $post->ID, 'whx4_events_detached_date', true ) ) . '</strong>. ';
        echo 'View original: <a href="' . esc_url( $url ) . '">Event #' . esc_html( $original_id ) . '</a>';
        echo '</p></div>';
    }

    // Revise or remove -- no longer using repeater rows for exclusions
    public static function handleExcludedDateRemovals( $post_id ): void
    {
        error_log( '=== class: EventInstances; method: handleExcludedDateRemovals ===' );

        if ( get_post_type( $post_id ) !== 'whx4_event' ) {
            return;
        }

        $removed = RepeaterChangeDetector::detectRemovedValues(
            $post_id,
            'whx4_events_excluded_dates',
            'whx4_events_exdate_date'
        );
        error_log( 'removed: ' . print_r($removed,true) );

        $pending = [];

        foreach ( $removed as $date ) {
            if ( self::replacementExists( $post_id, $date ) ) {
                $pending[] = $date;
            }
        }
        error_log( 'pending: ' . print_r($pending,true) );

        if ( $pending ) {
            set_transient( "whx4_events_cleanup_{$post_id}", $pending, 600 );
        }
    }

    public static function maybeShowDetachedCleanupNotice(): void
    {
        global $post;

        if ( ! $post || get_post_type( $post ) !== 'whx4_event' ) {
            return;
        }

        $dates = get_transient( "whx4_events_cleanup_{$post->ID}" );
        if ( ! $dates ) {
            return;
        }

        delete_transient( "whx4_events_cleanup_{$post->ID}" );

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Detached replacement events exist for removed exclusions:</strong></p><ul>';

        foreach ( $dates as $date ) {
            $trash_url = wp_nonce_url( add_query_arg([
                'whx4_trash_detached' => 1,
                'event_id' => $post->ID,
                'date' => $date,
            ]), 'whx4_trash_detached_event', '_wpnonce' );

            echo '<li>' . esc_html( $date ) . ' ';
            echo '<a href="' . esc_url( $trash_url ) . '" class="button button-small">Trash Replacement</a>';
            echo '</li>';
        }

        echo '</ul></div>';
    }

    public static function handleDetachedCleanupRequest(): void
    {
        if (
            ! isset( $_GET['whx4_trash_detached'], $_GET['event_id'], $_GET['date'], $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( $_GET['_wpnonce'], 'whx4_trash_detached_event' )
        ) {
            return;
        }

        $event_id = (int) $_GET['event_id'];
        $date = sanitize_text_field( $_GET['date'] );

        $query = new \WP_Query([
            'post_type' => 'event',
            'post_status' => [ 'publish', 'draft', 'pending' ],
            'meta_query' => [
                [
                    'key' => 'whx4_events_detached_from',
                    'value' => $event_id,
                ],
                [
                    'key' => 'whx4_events_detached_date',
                    'value' => $date,
                ],
            ],
            'fields' => 'ids',
        ]);

        foreach ( $query->posts as $post_id ) {
            wp_trash_post( $post_id );
        }

        wp_safe_redirect( admin_url( 'post.php?post=' . $event_id . '&action=edit' ) );
        exit;
    }

    public static function getOverrideDates( int $parent_id ): array
    {
        $args = [
            'post_type'      => 'whx4_event',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'whx4_events_detached_from',
                    'value'   => $parent_id,
                    'compare' => '=',
                ],
                [
                    'key'     => 'whx4_events_detached_date',
                    'compare' => 'EXISTS',
                ],
            ],
            'fields' => 'ids',
        ];

        $replacements = get_posts( $args );
        $map = [];

        foreach ( $replacements as $post_id ) {
            $original = get_post_meta( $post_id, 'whx4_events_detached_date', true );
            //$start    = get_field( 'whx4_events_start_date', $post_id );
            $startDT = DateHelper::combineDateAndTime(
                get_post_meta( $post_id, 'whx4_events_start_date', true ),
                get_post_meta( $post_id, 'whx4_events_start_time', true )
            );

            if ( $original && $startDT instanceof \DateTimeInterface ) {
                $map[ $original ] = [
                    'datetime' => $startDT,
                    'post_id'  => $post_id,
                ];
            }
        }

        return $map;
    }

    public static function getDetachedPostId( int $parent_id, string $date_str ): ?int
    {
        $query = new \WP_Query([
            'post_type'      => 'whx4_event',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => 'whx4_events_detached_from', //'whx4_events_parent_event',
                    'value' => $parent_id,
                    'compare' => '='
                ],
                [
                    'key'   => 'whx4_events_detached_date',
                    'value' => $date_str,
                    'compare' => '='
                ],
            ],
            'fields' => 'ids',
        ]);

        return $query->have_posts() ? (int) $query->posts[0] : null;
    }


    public static function enqueueAdminAssets(): void
    {
        global $post;

        if ( ! $post || get_post_type( $post ) !== 'whx4_event' ) {
            return;
        }

        wp_enqueue_style(
            'whx4-event-admin-styles',
            PluginPaths::url( 'src/Modules/Events/Assets/whx4-events-admin.css' ),
            [],
            //filemtime( PluginPaths::url( 'src/Modules/Events/Assets/whx4-events-admin.css' ), )
        );

        wp_enqueue_script(
            'whx4-event-overrides',
            PluginPaths::url( 'src/Modules/Events/Assets/event-overrides.js' ),
            //plugins_url( '/assets/js/event-overrides.js', dirname( __DIR__, 2 ) ), // adjust if needed
            [ 'jquery' ],
            null,
            true
        );

        wp_enqueue_script(
            'whx4-events-admin',
            PluginPaths::url( 'src/Modules/Events/Assets/whx4-events-admin.js' ),
            //plugins_url( '/assets/js/whx4-events-admin.js', __FILE__ ),
            [],
            '1.0',
            true
        );

        wp_localize_script( 'whx4-events-admin', 'whx4EventsAjax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'whx4_events_nonce' ),
        ]);
    }

}
