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
                    'key'   => 'field_whx4_events_start_date',
                    'label' => 'Start Date ',
                    'name'  => 'whx4_events_start_date',
                    'type'  => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format'  => 'Y-m-d',
                    'required' => 1,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '20',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key'   => 'field_whx4_events_start_time',
                    'label' => 'Start Time',
                    'name'  => 'whx4_events_start_time',
                    'type'  => 'time_picker',
                    'display_format' => 'H:i',
                    'return_format'  => 'H:i:s',
                    'required' => 1,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '20',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key'   => 'field_whx4_events_end_date',
                    'label' => 'End Date',
                    'name'  => 'whx4_events_end_date',
                    'type'  => 'date_picker',
                    'required' => 0,
                    'display_format' => 'Y-m-d',
                    'return_format'  => 'Y-m-d',
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '20',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key'   => 'field_whx4_events_end_time',
                    'label' => 'End Time',
                    'name'  => 'whx4_events_end_time',
                    'type'  => 'time_picker',
                    'required' => 0,
                    'display_format' => 'H:i',
                    'return_format'  => 'H:i:s',
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '20',
                        'class' => '',
                        'id' => '',
                    ],
                ],
                [
                    'key' => 'field_whx4_events_is_recurring',
                    'label' => 'Recurring Event?',
                    'name' => 'whx4_events_is_recurring',
                    'type' => 'true_false',
                    'ui' => 1,
                ],
                // START recurrence rules WIP
                [
                    'key' => 'field_whx4_events_recurrence_human',
                    'label' => 'Repeats',
                    'name' => 'whx4_events_recurrence_human',
                    'type' => 'group',
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_whx4_events_freq',
                            'label' => 'Frequency',
                            'name' => 'freq',
                            'type' => 'select',
                            'choices' => [
                                'DAILY'   => 'Daily',
                                'WEEKLY'  => 'Weekly',
                                'MONTHLY' => 'Monthly',
                                'YEARLY'  => 'Yearly',
                            ],
                            'required' => 1,
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                        [
                            'key' => 'field_whx4_events_interval',
                            'label' => 'Interval',
                            'name' => 'interval',
                            'type' => 'number',
                            'default_value' => 1,
                            'min' => 1,
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                        [
                            'key' => 'field_whx4_events_byday',
                            'label' => 'Days of the Week',
                            'name' => 'byday',
                            'type' => 'checkbox',
                            'choices' => [
                                'MO' => 'Monday',
                                'TU' => 'Tuesday',
                                'WE' => 'Wednesday',
                                'TH' => 'Thursday',
                                'FR' => 'Friday',
                                'SA' => 'Saturday',
                                'SU' => 'Sunday',
                            ],
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_whx4_events_freq',
                                        'operator' => '==',
                                        'value' => 'WEEKLY',
                                    ]
                                ]
                            ],
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                        [
                            'key' => 'field_whx4_events_bymonth',
                            'label' => 'Months',
                            'name' => 'bymonth',
                            'type' => 'checkbox',
                            'choices' => [
                                '1' => 'January',
                                '2' => 'February',
                                '3' => 'March',
                                '4' => 'April',
                                '5' => 'May',
                                '6' => 'June',
                                '7' => 'July',
                                '8' => 'August',
                                '9' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December',
                            ],
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_whx4_events_freq',
                                        'operator' => '==',
                                        'value' => 'YEARLY',
                                    ]
                                ]
                            ],
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                        [
                            'key' => 'field_whx4_events_count',
                            'label' => 'Number of occurrences',
                            'name' => 'count',
                            'type' => 'number',
                            'min' => 1,
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                        [
                            'key' => 'field_whx4_events_until',
                            'label' => 'Repeat until',
                            'name' => 'until',
                            'type' => 'date_picker',
                            'display_format' => 'Y-m-d',
                            'return_format'  => 'Y-m-d',
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                    ],
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_whx4_events_is_recurring',
                                'operator' => '==',
                                'value' => '1',
                            ]
                        ]
                    ],
                ],
                [
                    'key'           => 'field_whx4_events_excluded_dates',
                    'label'         => 'Excluded Dates',
                    'name'          => 'whx4_events_excluded_dates',
                    'type'          => 'repeater',
                    'instructions'  => 'Dates to skip in this recurrence series.',
                    'min'           => 0,
                    'layout'        => 'row',
                    'sub_fields'    => [
                        [
                            'key'           => 'field_whx4_events_exdate_date',
                            'label'         => 'Date to Exclude',
                            'name'          => 'whx4_events_exdate_date',
                            'type'          => 'date_picker',
                            'display_format'=> 'Y-m-d',
                            'return_format' => 'Y-m-d',
                            'wrapper' => [
                                'width' => '50',
                                'class' => '',
                                'id' => '',
                            ],
                        ],
                    ],
                    'wrapper' => [
                        'width' => '40',
                        'class' => '',
                        'id' => '',
                    ],
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_whx4_events_is_recurring',
                                'operator' => '==',
                                'value' => '1',
                            ]
                        ]
                    ],
                ],
                /*[
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
                ],*/
                [
                    'key'   => 'field_whx4_events_rrule',
                    'label' => 'Recurrence Rule',
                    'name'  => 'whx4_events_rrule',
                    'type'  => 'text',
                    'instructions' => 'iCal RRULE format (e.g. FREQ=WEEKLY;BYDAY=MO,WE)',
                    'required' => 0,
                    'readonly' => 1,
                    'wrapper' => [
                        'width' => '20',
                        'class' => '',
                        'id' => '',
                    ],
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_whx4_events_is_recurring',
                                'operator' => '==',
                                'value' => '1',
                            ]
                        ]
                    ],
                ],
                // END recurrence rules WIP
                [
                    'key'   => 'field_whx4_events_legacy_em_event_id',
                    'label' => 'Legacy EM Event ID',
                    'name'  => 'whx4_events_legacy_em_event_id',
                    'type'  => 'number',
                    'readonly' => 1,
                    'wrapper' => [
                        'width' => '20',
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
