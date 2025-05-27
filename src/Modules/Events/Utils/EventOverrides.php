<?php

namespace atc\Whx4\Modules\Events\Utils;

use WP_Post;
use WP_Query;

class EventOverrides
{
    public static function register(): void
    {
        add_action( 'add_meta_boxes', [self::class, 'addMetaBox'] );
        add_action( 'admin_init', [self::class, 'handleCreateRequest'] );
        add_action( 'edit_form_top', [self::class, 'maybeAddDetachedNotice'] );
        add_action( 'admin_enqueue_scripts', [self::class, 'enqueueAdminAssets'] );
        add_action( 'wp_ajax_whx4_check_replacement', [ self::class, 'ajaxCheckReplacement' ] );
        //add_action( 'wp_ajax_whx4_check_replacement', [ \smith\Rex\Events\Admin\EventOverrides::class, 'ajaxCheckReplacement' ] );
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
        //$excluded = get_post_meta( $post->ID, 'whx4_events_excluded_dates', true ) ?: [];
        $excluded_rows = get_field( 'whx4_events_excluded_dates', $post->ID ) ?: [];

        if ( empty( $excluded_rows ) ) {
            echo '<p>No excluded dates.</p>';
            return;
        }

        echo '<ul>';
        foreach ( $excluded_rows as $row ) {
            if ( ! empty( $row['whx4_events_exdate_date'] ) ) {
                $date = $row['whx4_events_exdate_date'];
            } else {
                next;
            }
            if ( self::replacementExists( $post->ID, $date ) ) {
                echo '<li>' . esc_html( $date ) . ' <em>Replacement created</em></li>';
            } else {
                $url = add_query_arg([
                    'whx4_create_detached_event' => 1,
                    'event_id' => $post->ID,
                    'date' => $date,
                    '_wpnonce' => wp_create_nonce( 'whx4_create_detached_event' ),
                ], admin_url( 'edit.php?post_type=event' ) );

                /*echo '<li data-exclusion-date="' . esc_attr( $date ) . '">';
                echo esc_html( $date ) . ' ';
                echo '<a class="button button-small whx4-create-replacement-btn" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">Create replacement</a>';
                echo '</li>';*/
                echo '<li data-exclusion-date="' . esc_attr( $date ) . '">';
                echo esc_html( $date ) . ' ';
                echo '<a class="button button-small whx4-create-replacement-btn"
                           href="' . esc_url( $url ) . '"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-event-id="' . esc_attr( $post->ID ) . '"
                           data-date="' . esc_attr( $date ) . '">Create replacement</a>';
                echo '</li>';

            }
        }
        echo '</ul>';

        $replacements = new WP_Query([
            'post_type' => 'whx4_event', //'event',
            'post_status' => [ 'publish', 'draft', 'pending' ],
            'meta_key' => 'whx4_events_detached_from',
            'meta_value' => $post->ID,
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ]);

        if ( $replacements->have_posts() ) {
            echo '<hr><strong>Detached replacements:</strong><ul>';
            foreach ( $replacements->posts as $replacement ) {
                $detached_date = get_post_meta( $replacement->ID, 'whx4_events_detached_date', true );
                $edit_url = get_edit_post_link( $replacement->ID );
                echo '<li>';
                echo esc_html( $detached_date ) . ' – ';
                echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( get_the_title( $replacement ) ) . '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
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

    public static function enqueueAdminAssets(): void
    {
        global $post;

        if ( ! $post || get_post_type( $post ) !== 'event' ) {
            return;
        }

        wp_enqueue_script(
            'whx4-event-overrides',
            plugins_url( '/assets/js/event-overrides.js', dirname( __DIR__, 2 ) ), // adjust if needed
            [ 'jquery' ],
            null,
            true
        );
    }

}
