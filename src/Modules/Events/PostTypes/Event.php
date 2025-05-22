<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Utils\Recurrence\InstanceGenerator;

class Event extends PostTypeHandler
{
    public function __construct(WP_Post|null $post = null)
    {
		$slug = apply_filters( 'whx4_events_post_type_slug', 'whx4_event' );

		$config = [
			'slug'        => $slug,
			'labels'      => [
				'name' => 'WHx4 Events',
				'singular_name' => 'WHx4 Event',
			],
            'supports'    => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
			//'taxonomies' => [ 'event_category', 'event_tag', 'admin_tag' ],
		];

		parent::__construct( $config, $post );
	}

	public function boot(): void
	{
	    parent::boot(); // Optional if you add shared logic later

		$this->applyTitleArgs( $this->getSlug(), [
			'line_breaks'    => true,
			'show_subtitle'  => true,
			'hlevel_sub'     => 4,
			'called_by'      => 'Event::boot',
			//'append'         => 'TEST: ',
		]);

		add_action( 'acf/save_post', [ $this, 'generateRruleFromFields' ], 20 );
		//add_filter( 'acf/prepare_field/name=whx4_events_recurrence_human', [ $this, 'addRecurrencePreview' ] );
		add_filter( 'acf/prepare_field/name=whx4_events_recurrence_rule', [ $this, 'addRecurrencePreview' ] );
	}

	public function generateRruleFromFields( $post_id ): void
	{
		if ( get_post_type( $post_id ) !== 'whx4_event' ) {
			return;
		}

		$input = get_field( 'whx4_events_recurrence_human', $post_id );

		if ( !$input || !is_array( $input ) || empty( $input['freq'] ) ) {
			// If no new input, but rrule already exists, preserve it
			if ( !get_post_meta( $post_id, 'whx4_events_recurrence_rule', true ) ) {
				update_post_meta( $post_id, 'whx4_events_recurrence_rule', '' );
			}
			return;
		}


		$rule = [ 'FREQ' => strtoupper( $input['freq'] ) ];

		if ( !empty( $input['interval'] ) && intval( $input['interval'] ) > 1 ) {
			$rule['INTERVAL'] = intval( $input['interval'] );
		}

		if ( !empty( $input['byday'] ) && is_array( $input['byday'] ) ) {
			$rule['BYDAY'] = implode( ',', array_map( 'strtoupper', $input['byday'] ) );
		}

		if ( !empty( $input['bymonth'] ) && is_array( $input['bymonth'] ) ) {
			$rule['BYMONTH'] = implode( ',', array_map( 'intval', $input['bymonth'] ) );
		}

		if ( !empty( $input['count'] ) ) {
			$rule['COUNT'] = intval( $input['count'] );
		}

		if ( !empty( $input['until'] ) ) {
			$rule['UNTIL'] = date( 'Ymd\THis\Z', strtotime( $input['until'] ) );
		}

		$parts = [];
		foreach ( $rule as $k => $v ) {
			$parts[] = "{$k}={$v}";
		}

		$rrule = implode( ';', $parts );
		update_post_meta( $post_id, 'whx4_events_recurrence_rule', $rrule );
	}

	public function addRecurrencePreview( $field )
	{
		if ( !function_exists( 'get_current_screen' ) ) {
			return $field;
		}

		$post_id = get_the_ID();
		$rrule   = get_post_meta( $post_id, 'whx4_events_recurrence_rule', true );

		if ( !$rrule ) {
			return $field;
		}

		try {
			$summary = new \atc\WHx4\Utils\Recurrence\RecurrenceSummaryBuilder( $rrule );
			$text = $summary->getText();

			$field['instructions'] .= '<br><strong>Preview:</strong> ' . esc_html( $text );
		} catch ( \Throwable $e ) {
			// optionally log or ignore
		}

		return $field;
	}

	/*
	// WIP: generate instances for display in calendar



		$start = get_post_meta( $post->ID, 'whx4_events_start_datetime', true );
		$rrule = get_post_meta( $post->ID, 'whx4_events_recurrence_rule', true );

		$exdates = [];
		foreach ( get_field( 'whx4_events_excluded_datetimes', $post->ID ) ?: [] as $row ) {
			if ( !empty( $row['datetime'] ) ) {
				$exdates[] = ( new DateTimeImmutable( $row['datetime'] ) )->format( 'Y-m-d\TH:i:s' );
			}
		}

		$overrides = [];
		foreach ( get_field( 'whx4_events_instance_overrides', $post->ID ) ?: [] as $row ) {
			if ( !empty( $row['original'] ) && !empty( $row['replacement'] ) ) {
				$key = ( new DateTimeImmutable( $row['original'] ) )->format( 'Y-m-d\TH:i:s' );
				$overrides[ $key ] = new DateTimeImmutable( $row['replacement'] );
			}
		}

		$generator = new InstanceGenerator([
			'start'     => $start,
			'rrule'     => $rrule,
			'exdates'   => $exdates,
			'overrides' => $overrides,
		]);

		$instances = $generator->generate( 50 );

	*/


}
