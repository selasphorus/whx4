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
        $old_rows = get_post_meta( $post_id, $repeater_field_name, true ) ?: [];
        $old_values = [];

        foreach ( $old_rows as $row ) {
            if ( isset( $row[ $subfield_name ] ) ) {
                $old_values[] = $row[ $subfield_name ];
            }
        }

        $new_values = [];

        foreach ( $_POST['acf'] as $key => $value ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $row ) {
                    if ( is_array( $row ) && isset( $row[ $subfield_name ] ) ) {
                        $new_values[] = $row[ $subfield_name ];
                    }
                }
            }
        }

        return array_diff( $old_values, $new_values );
    }
}
