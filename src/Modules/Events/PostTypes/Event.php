<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Helpers\FieldDisplayHelpers;
use atc\WHx4\Modules\Events\Utils\InstanceGenerator;

class Event extends PostTypeHandler
{
    /*public function getSlug(): string
    {
        $slug = EnvSwitch::value('event', [
            [
                'when' => static fn() => Plugins::classExists('\EM_Event')
                    || Plugins::isActive('events-manager/events-manager.php'),
                'then' => 'rex_event',
            ],
            [
                'when' => static fn() => Plugins::isActive('the-events-calendar/the-events-calendar.php'),
                'then' => 'rex_event',
            ],
        ]);

        // Allow explicit override if needed. -- Example: add_filter('rex/events/event_slug', fn() => 'my_event');
        return (string) apply_filters('rex/events/event_slug', $slug);
    }*/

    //
    public function __construct(WP_Post|null $post = null)
    {
		//$slug = apply_filters( 'whx4_events_post_type_slug', 'whx4_event' );
		$slug = $this->resolveSlug();

		$config = [
			'slug'        => $slug,
			'labels'      => [
				'name' => 'WHx4 Events',
				'singular_name' => 'WHx4 Event',
			],
            'supports'    => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
			'taxonomies' => [ 'event_category', 'event_tag', 'program_label', 'admin_tag' ],
			'menu_icon' => 'dashicons-calendar-alt',
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
		add_filter( 'acf/load_value/name=whx4_events_excluded_dates', function( $value ) {
			return FieldDisplayHelpers::formatArrayForDisplay( $value );
		}, 10 );
	}

	public function generateRruleFromFields( $post_id ): void
	{
		if ( get_post_type( $post_id ) !== 'whx4_event' ) {
			return;
		}

		$input = get_field( 'whx4_events_recurrence_human', $post_id );

		if ( !$input || !is_array( $input ) || empty( $input['freq'] ) ) {
			// If no new input, but rrule already exists, preserve it
			if ( !get_post_meta( $post_id, 'whx4_events_rrule', true ) ) {
				update_post_meta( $post_id, 'whx4_events_rrule', '' );
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
		update_post_meta( $post_id, 'whx4_events_rrule', $rrule );
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

	/**
 * Decide the CPT slug at runtime, with legacy + new filters.
 * Default: 'event'; use 'rex_event' if a known events plugin is active.
 */
	protected function resolveSlug(): string
	{
		$base = $this->conflictingEventsPluginActive() ? 'whx4_event' : 'event';

		// New, clearer filter (preferred going forward)
		$slug = (string) apply_filters('whx4/events/event_slug', $base);

		// Back-compat for existing code that already filters this
		//$slug = (string) apply_filters('whx4_events_post_type_slug', $slug);

		return $slug;
	}

	/**
	 * Keep this tiny and fast: check a couple of high-signal conditions.
	 */
	private function conflictingEventsPluginActive(): bool
	{
		// Fast class check(s)
		if ( class_exists('\EM_Event') ) {
			return true;
		}

		if ( ! function_exists('is_plugin_active') ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// File checks (adjust to your targets)
		if ( is_plugin_active('events-manager/events-manager.php') ) {
			return true;
		}

		if ( is_plugin_active('the-events-calendar/the-events-calendar.php') ) {
			return true;
		}

		// Multisite network-active check (optional, cheap)
		/*
		if ( is_multisite() ) {
			$network = (array) get_site_option('active_sitewide_plugins', []);
			if ( isset($network['events-manager/events-manager.php']) || isset($network['the-events-calendar/the-events-calendar.php']) ) {
				return true;
			}
		}
		*/

		return false;
	}
}
