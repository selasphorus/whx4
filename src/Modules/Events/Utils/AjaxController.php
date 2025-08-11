<?php

namespace atc\WHx4\Modules\Events\Utils;

use atc\WHx4\Core\ViewLoader;
use atc\WHx4\Modules\Events\Utils\EventInstances;

class AjaxController
{
    public static function register(): void
    {
        add_action( 'wp_ajax_whx4_create_replacement', [ self::class, 'handleCreateReplacement' ] );
        //add_action( 'wp_ajax_whx4_check_replacement', [self::class, 'checkReplacement'] );
        add_action( 'wp_ajax_whx4_exclude_date', [ self::class, 'excludeDate' ] );
        add_action( 'wp_ajax_whx4_unexclude_date', [ self::class, 'unexcludeDate' ] );
    }

    public static function excludeDate(): void
    {
        self::handleExcludeToggle( true );
    }

    public static function unexcludeDate(): void
    {
        self::handleExcludeToggle( false );
    }

    private static function handleExcludeToggle( bool $exclude ): void
    {
        check_ajax_referer( 'whx4_events_nonce', 'nonce' );

        $postID  = absint( $_POST['post_id'] ?? 0 );
        $dateStr = sanitize_text_field( $_POST['date'] ?? '' );

        if ( ! $postID || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dateStr ) ) {
            wp_send_json_error( [ 'message' => 'Invalid request' ] );
        }

        $excluded = maybe_unserialize( get_post_meta( $postID, 'whx4_events_excluded_dates', true ) ) ?: [];

        if ( $exclude ) {
            if ( ! in_array( $dateStr, $excluded, true ) ) {
                $excluded[] = $dateStr;
                sort( $excluded );
            }
        } else {
            $excluded = array_filter( $excluded, fn( $d ) => $d !== $dateStr );
        }

        update_post_meta( $postID, 'whx4_events_excluded_dates', $excluded );

        $replacement_id = EventInstances::getDetachedPostId( $postID, $dateStr ); // maybe better to handle replacements as array insted of this approach

        $html = ViewLoader::renderToString( 'event-instance-div', [
            'post_id'        => $postID,
            'date_str'       => $dateStr,
            'excluded'       => in_array( $dateStr, $excluded, true ),
            'replacement_id' => $replacement_id,
        ], 'events' );

        wp_send_json_success( [ 'html' => $html ] );
    }

    private static function handleCreateReplacement(): void
    {
        check_ajax_referer( 'whx4_events_nonce', 'nonce' );
        //check_ajax_referer( 'whx4_create_detached_event', '_wpnonce' );

        $postID  = absint( $_POST['post_id'] ?? 0 );
        //$parent_id = (int) $_POST['event_id'];
        $dateStr = sanitize_text_field( $_POST['date'] ?? '' );

        if ( ! $postID || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dateStr ) ) {
            wp_send_json_error( [ 'message' => 'Invalid request' ] );
        }

        if ( ! current_user_can( 'edit_post', $postID ) ) {
            wp_send_json_error( 'Permission denied', 403 );
        }


        // Create replacement if it doesn’t exist already
        $replacement_id = self::getDetachedPostId( $postID, $date );
        if ( $replacement_id ) {
            $url = get_edit_post_link( $replacement_id );
            wp_send_json_success( [ 'exists' => true, 'edit_url' => $url ] );
        }

        $new_id = self::handleCreateRequest( $postID, $date );
        //$new_id = self::createDetachedReplacement( $postID, $date );
        if ( is_wp_error( $new_id ) || ! $new_id ) {
            wp_send_json_error( [ 'message' => 'Failed to create replacement.' ] );
        }

        wp_send_json_success( [
            'exists'    => false,
            'created'   => true,
            'edit_url'  => get_edit_post_link( $new_id )
        ] );


        // Check to see if replacement event already exists for the date in question
        $exists = EventInstances::replacementExists( $parent_id, $date );
        //wp_send_json_success([ 'exists' => $exists ]);

        // If no replacement exists, then insert a new post


    }
}
