<?php

namespace atc\WHx4\Utils;

class RepeaterChangeDetector
{
    /**
     * Detect removed repeater rows based on a subfield.
     *
     * @param int $post_id
     * @param string $repeater_field_name The meta key (e.g. 'whx4_events_excluded_dates')
     * @param string $subfield_name The subfield name (e.g. 'whx4_events_exdate_date')
     * @return string[] List of removed values
     */
    public static function detectRemovedValues( int $post_id, string $repeater_field_name, string $subfield_name ): array
    {
        error_log( '=== class: RepeaterChangeDetector; method: detectRemovedValues ===' );
        error_log( 'post_id: '. $post_id . '; repeater_field_name: '.$repeater_field_name . '; subfield_name: '.$subfield_name );

        $old_rows = get_field( $repeater_field_name, $post_id ) ?: []; //$old_rows = get_post_meta( $post_id, $repeater_field_name, true ) ?: [];
        error_log( 'old_rows: ' . print_r($old_rows,true) );

        $old_values = [];

        if ( is_array( $old_rows ) ) {
            foreach ( $old_rows as $row ) {
                if ( isset( $row[ $subfield_name ] ) ) {
                    $old_values[] = $row[ $subfield_name ];
                }
            }
        }
        error_log( 'old_values: ' . print_r($old_values,true) );
        //error_log( '_POST[acf]: ' . print_r($_POST['acf'],true) );

        $new_values = [];

        $field_key = 'field_'.$repeater_field_name;
        error_log( '_POST[acf][field_key]: ' . print_r($_POST['acf'][$field_key],true) );

        foreach ( $_POST['acf'][$field_key] as $key => $value ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $row ) {
                    if ( is_array( $row ) && isset( $row[ $subfield_name ] ) ) {
                        $new_values[] = $row[ $subfield_name ];
                    }
                }
            }
        }
        error_log( 'new_values: ' . print_r($new_values,true) );

        return array_diff( $old_values, $new_values );
    }
}
