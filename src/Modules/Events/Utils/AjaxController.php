<?php

namespace atc\WHx4\Modules\Events\Utils;

use atc\WHx4\Modules\Events\Utils\EventOverrides;

class AjaxController
{
    public static function register(): void
    {
        //add_action( 'wp_ajax_whx4_check_replacement', [self::class, 'checkReplacement'] );
        add_action( 'wp_ajax_whx4_exclude_date', [ AjaxController::class, 'excludeDate' ] );
        add_action( 'wp_ajax_whx5_unexclude_date', [ AjaxController::class, 'unexcludeDate' ] );

    }

    public static function excludeDate(): void {
        check_ajax_referer( 'whx4_events_nonce', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );
        $date = sanitize_text_field( $_POST['date'] ?? '' );

        if ( ! $post_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( [ 'message' => 'Invalid request' ] );
        }

        $excluded = get_post_meta( $post_id, 'whx4_events_excluded_dates', true ) ?: [];

        if ( ! in_array( $date, $excluded, true ) ) {
            $excluded[] = $date;
            sort( $excluded );
            update_post_meta( $post_id, 'whx4_events_excluded_dates', $excluded );
        }

        wp_send_json_success();
    }

    public static function unexcludeDate(): void {
        check_ajax_referer( 'whx4_events_nonce', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );
        $date = sanitize_text_field( $_POST['date'] ?? '' );

        $excluded = get_post_meta( $post_id, 'whx4_events_excluded_dates', true ) ?: [];

        $new = array_filter( $excluded, fn( $d ) => $d !== $date );

        update_post_meta( $post_id, 'whx4_events_excluded_dates', $new );

        wp_send_json_success();
    }

    public static function checkReplacement(): void
    {
        check_ajax_referer( 'whx4_create_detached_event', '_wpnonce' );

        $parent_id = (int) $_POST['event_id'];
        $date = sanitize_text_field( $_POST['date'] );

        if ( ! current_user_can( 'edit_post', $parent_id ) ) {
            wp_send_json_error( 'Permission denied', 403 );
        }

        $exists = EventOverrides::replacementExists( $parent_id, $date );
        wp_send_json_success([ 'exists' => $exists ]);
    }
}
