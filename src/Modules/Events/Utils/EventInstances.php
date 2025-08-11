<?php

namespace atc\WHx4\Modules\Events\Utils;

use WP_Post;
use WP_Query;
use atc\WHx4\Utils\DateHelper;
use atc\WHx4\Utils\RepeaterChangeDetector;
use atc\WHx4\Helpers\PluginPaths;
use atc\Whx4\Core\ViewLoader;

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
        //add_action( 'wp_ajax_whx4_create_replacement', [ self::class, 'handleCreateReplacement' ] );
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

    public static function renderMetaBox( \WP_Post $post ): void
    {
        /*$instances = self::getInstancesForPost( $post->ID );
        $excluded = self::getExcludedDates( $post->ID );
        $replacements = self::getReplacementMap( $post->ID );*/

        $postID = $post->ID;
        $instances = InstanceGenerator::fromPostId( $postID, 50, true ); // set limit higher than 50?
        $excluded = maybe_unserialize( get_post_meta( $postID, 'whx4_events_excluded_dates', true ) ) ?: [];
        //$replacements = maybe_unserialize( get_post_meta( $postID, 'whx4_events_replaced_dates', true ) ?: []; );
        $replacements = [];

        foreach ( $instances as $date ) {
            $dateStr = $date->format( 'Y-m-d' );
            $replacements[ $dateStr ] = self::getDetachedPostId( $postID, $dateStr );
        }

        ViewLoader::render( 'event-instances-columnar-list', [
            'post_id'     => $postID,
            'instances'   => $instances,
            'excluded'    => $excluded,
            'replacements'=> $replacements,
        ], 'events' );
    }

    public static function getInstanceDivHtml( int $postID, string $date ): string
    {
        ob_start();

        // You can reuse a template part or include logic here directly.
        // Example: template partial to render a single instance row
        $context = [
            'post_id' => $postID,
            'date'    => $date,
            'actions' => self::getAvailableActions( $postID, $date ), // if needed
        ];

        ViewLoader::render( 'event-instance-div', [
            'post_id'     => $postID,
            'instances'   => $instances,
            'excluded'    => $excluded,
            //'replacements'=> $replacements,
        ], 'events' );

        return ob_get_clean();
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

    //public static function getDetachedPostId( int $parent_id, string $dateStr ): ?int
    protected static function replacementExists( int $parent_id, string $dateStr ): bool
    {
        $query = new WP_Query([
            'post_type' => 'whx4_event', //'event',
            'post_status' => [ 'publish', 'draft', 'pending' ],
            'meta_query' => [
                [
                    'key' => 'whx4_events_detached_from',
                    'value' => $parent_id,
                    //'compare' => '='
                ],
                [
                    'key' => 'whx4_events_detached_date',
                    'value' => $dateStr,
                    //'compare' => '='
                ],
            ],
            'fields' => 'ids',
        ]);

        return ! empty( $query->posts );
        //return $query->have_posts() ? (int) $query->posts[0] : null;
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
    public static function handleExcludedDateRemovals( $postID ): void
    {
        error_log( '=== class: EventInstances; method: handleExcludedDateRemovals ===' );

        if ( get_post_type( $postID ) !== 'whx4_event' ) {
            return;
        }

        $removed = RepeaterChangeDetector::detectRemovedValues(
            $postID,
            'whx4_events_excluded_dates',
            'whx4_events_exdate_date'
        );
        error_log( 'removed: ' . print_r($removed,true) );

        $pending = [];

        foreach ( $removed as $date ) {
            if ( self::replacementExists( $postID, $date ) ) {
                $pending[] = $date;
            }
        }
        error_log( 'pending: ' . print_r($pending,true) );

        if ( $pending ) {
            set_transient( "whx4_events_cleanup_{$postID}", $pending, 600 );
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

        foreach ( $query->posts as $postID ) {
            wp_trash_post( $postID );
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

        foreach ( $replacements as $postID ) {
            $original = get_post_meta( $postID, 'whx4_events_detached_date', true );
            //$start    = get_field( 'whx4_events_start_date', $postID );
            $startDT = DateHelper::combineDateAndTime(
                get_post_meta( $postID, 'whx4_events_start_date', true ),
                get_post_meta( $postID, 'whx4_events_start_time', true )
            );

            if ( $original && $startDT instanceof \DateTimeInterface ) {
                $map[ $original ] = [
                    'datetime' => $startDT,
                    'post_id'  => $postID,
                ];
            }
        }

        return $map;
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
