<?php

namespace atc\WHx4\Modules\Events\Fields;

use atc\WHx4\Core\Contracts\FieldGroupInterface;

class EventFields implements FieldGroupInterface
{
    public static function register(): void
    {
        error_log( 'EventFields::register' );

        if ( !function_exists('acf_add_local_field_group') ) return;

        acf_add_local_field_group([
            'key'    => 'group_event_fields',
            'title'  => 'Event Details',
            'fields' => [
                [
                    'key'   => 'field_whx4_events_start_datetime',
                    'label' => 'Start Date & Time',
                    'name'  => 'whx4_events_start_datetime',
                    'type'  => 'date_time_picker',
                    'required' => 1,
                    'display_format' => 'Y-m-d H:i',
                    'return_format'  => 'Y-m-d\TH:i:s',
                ],
                [
                    'key'   => 'field_whx4_events_end_datetime',
                    'label' => 'End Date & Time',
                    'name'  => 'whx4_events_end_datetime',
                    'type'  => 'date_time_picker',
                    'required' => 0,
                    'display_format' => 'Y-m-d H:i',
                    'return_format'  => 'Y-m-d\TH:i:s',
                ],
                [
                    'key'   => 'field_whx4_events_recurrence_rule',
                    'label' => 'Recurrence Rule',
                    'name'  => 'whx4_events_recurrence_rule',
                    'type'  => 'text',
                    'instructions' => 'iCal RRULE format (e.g. FREQ=WEEKLY;BYDAY=MO,WE)',
                    'required' => 0,
                ],
                [
                    'key'           => 'field_whx4_events_excluded_datetimes',
                    'label'         => 'Excluded Dates',
                    'name'          => 'whx4_events_excluded_datetimes',
                    'type'          => 'repeater',
                    'instructions'  => 'Dates to skip in this recurrence series.',
                    'min'           => 0,
                    'layout'        => 'row',
                    'sub_fields'    => [
                        [
                            'key'           => 'field_whx4_events_exdate_datetime',
                            'label'         => 'Date to Exclude',
                            'name'          => 'datetime',
                            'type'          => 'date_time_picker',
                            'display_format'=> 'Y-m-d H:i',
                            'return_format' => 'Y-m-d\TH:i:s',
                        ],
                    ],
                ],
                [
                    'key'           => 'field_whx4_events_instance_overrides',
                    'label'         => 'Modified Instances',
                    'name'          => 'whx4_events_instance_overrides',
                    'type'          => 'repeater',
                    'instructions'  => 'Customize individual dates in this series.',
                    'layout'        => 'row',
                    'sub_fields'    => [
                        [
                            'key'           => 'field_whx4_events_override_original',
                            'label'         => 'Original Date/Time',
                            'name'          => 'original',
                            'type'          => 'date_time_picker',
                            'required'      => 1,
                            'display_format'=> 'Y-m-d H:i',
                            'return_format' => 'Y-m-d\TH:i:s',
                        ],
                        [
                            'key'           => 'field_whx4_events_override_replacement',
                            'label'         => 'New Date/Time',
                            'name'          => 'replacement',
                            'type'          => 'date_time_picker',
                            'required'      => 0,
                            'display_format'=> 'Y-m-d H:i',
                            'return_format' => 'Y-m-d\TH:i:s',
                        ],
                        [
                            'key'           => 'field_whx4_events_override_note',
                            'label'         => 'Notes',
                            'name'          => 'note',
                            'type'          => 'text',
                            'required'      => 0,
                        ],
                    ],
                ],
                [
                    'key'   => 'field_whx4_events_legacy_em_event_id',
                    'label' => 'Legacy EM Event ID',
                    'name'  => 'whx4_events_legacy_em_event_id',
                    'type'  => 'number',
                    'readonly' => 1,
                    'wrapper' => [
                        'width' => '50',
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'event',
                    ],
                ],
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'whx4_event',
                    ],
                ]
            ],
        ]);
    }
}
