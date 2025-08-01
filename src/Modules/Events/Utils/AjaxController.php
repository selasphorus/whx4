<?php

namespace atc\WHx4\Modules\Events\Utils;

use atc\WHx4\Core\ViewLoader;
use atc\WHx4\Modules\Events\Utils\EventInstances;

class AjaxController
{
    public static function register(): void
    {
        add_action( 'wp_ajax_whx4_check_replacement', [self::class, 'checkReplacement'] );
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

    public static function checkReplacement(): void
    {
        check_ajax_referer( 'whx4_create_detached_event', '_wpnonce' );

        $parent_id = (int) $_POST['event_id'];
        $date = sanitize_text_field( $_POST['date'] );

        if ( ! current_user_can( 'edit_post', $parent_id ) ) {
            wp_send_json_error( 'Permission denied', 403 );
        }

        $exists = EventInstances::replacementExists( $parent_id, $date );
        wp_send_json_success([ 'exists' => $exists ]);
    }
}
