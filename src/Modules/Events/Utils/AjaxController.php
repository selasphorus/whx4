<?php

namespace atc\WHx4\Modules\Events\Utils;

use atc\WHx4\Modules\Events\Utils\EventOverrides;

class AjaxController
{
    public static function register(): void
    {
        add_action( 'wp_ajax_whx4_check_replacement', [self::class, 'checkReplacement'] );
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
