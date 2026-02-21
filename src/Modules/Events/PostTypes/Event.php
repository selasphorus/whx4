<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Contracts\QueryContributor;
use atc\WXC\Helpers\FieldDisplayHelpers;
use atc\WXC\Traits\AppliesScopeToMainQuery;
use atc\WXC\Query\ScopedDateResolver;
//
use atc\WHx4\Modules\Events\Utils\InstanceGenerator;
use atc\WHx4\Modules\Events\Utils\EventInstances;

class Event extends PostTypeHandler implements QueryContributor //, ListDisplayableInterface
{
    use AppliesScopeToMainQuery;
    public const DATE_META = 'whx4_events_start_date';
    
    public function __construct(?\WP_Post $post = null)
    {
		//$slug = apply_filters( 'whx4_events_post_type_slug', 'whx4_event' );
		$slug = $this->resolveSlug();

		$config = [
			'slug'        => $slug,
			'rewrite' => ['slug' => 'whx4_calendar'], // TODO: make conditional upon $slug
			'labels'      => [
				'name' => 'WHx4 Events',
				'singular_name' => 'WHx4 Event',
			],
            'supports' => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
			'taxonomies' => [ 'event_category', 'event_tag', 'program_label', 'admin_tag' ],
			'menu_icon' => 'dashicons-calendar-alt',
			'capability_type' => ['event','events'],
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
		
		// Register scope filtering
        $this->registerScopeFilter();
        
        // Expand instances after query runs
        add_filter('the_posts', [$this, 'expandRecurringInstances'], 999, 2);

		add_action( 'acf/save_post', [ $this, 'generateRruleFromFields' ], 20 );
		//add_filter( 'acf/prepare_field/name=whx4_events_recurrence_human', [ $this, 'addRecurrencePreview' ] );
		add_filter( 'acf/prepare_field/name=whx4_events_recurrence_rule', [ $this, 'addRecurrencePreview' ] );
		add_filter( 'acf/load_value/name=whx4_events_excluded_dates', function( $value ) {
			return FieldDisplayHelpers::formatArrayForDisplay( $value );
		}, 10 );
	}
    
	/**
	 * Decide the CPT slug at runtime, with legacy + new filters.
	 * Default: 'event'; use 'whx4_event' if a known events plugin is active.
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
	 * Canonical allow-list of URL params that can shape Transaction queries.
	 * Consumers (shortcodes, controllers, handler methods) can pass this to a
	 * UrlParamBridge to sanitize & map into PostQuery inputs.
	 */
	public static function allowedUrlParams(): array
	{
		$spec = [
			'scope' => [
				'sanitize' => [PostTypeHandler::class, 'sanitizeScopeParam'],
				'map_to'   => ['arg' => 'scope'], // PostQuery will forward to ScopedDateResolver
				'override' => true,
			],
			'event_category' => [
				'sanitize' => [PostTypeHandler::class, 'sanitizeTermSlugsParam'],
				'map_to'   => ['tax' => 'event_category', 'field' => 'slug'],
				'override' => true,
			],
			/*
			'related_group' => [
			    'sanitize' => [PostTypeHandler::class, 'sanitizePostIdOrSlugParam'],
			    'map_to'   => ['arg' => 'related_group'],
			    'override' => true,
			],*/
		];

		// Optional extension point for add-ons/themes.
		return apply_filters('whx4_allowed_url_params_event', $spec);
	}

	protected static function getQuerySpec(): array
    {
        return [
            //'cpt' => $this->resolveSlug(),?
            'cpt' => 'whx4_event',
            'date_meta' => [
                // TODO/WIP: rethink the default and field types
                // Current storage model: separate DATE fields; revisit DATETIME later
                'key' => 'whx4_events_start_date',
                //'start_key' => 'whx4_events_start_date',
                //'end_key'   => 'whx4_events_end_date',
                'meta_type' => 'DATE', // Keep DATE for now; revisit DATETIME later
            ],
            'taxonomies' => [ 'event_category' ],
            'defaults' => [
                'limit'  => 10,
                'order'  => 'ASC',
                'orderby'=> 'meta_value',
                'scope'=> 'this_year', // ???
            ],
            'allowed_orderby' => ['meta_value','date','title','menu_order','modified'],
            'default_view'    => 'list',
        ];
    }

	// WIP re transition from EM
    /*
    public function getSlug(): string
    {
        $slug = EnvSwitch::value('event', [
            [
                'when' => static fn() => Plugins::classExists('\EM_Event')
                    || Plugins::isActive('events-manager/events-manager.php'),
                'then' => 'whx4_event',
            ],
            [
                'when' => static fn() => Plugins::isActive('the-events-calendar/the-events-calendar.php'),
                'then' => 'whx4_event',
            ],
        ]);

        // Allow explicit override if needed. -- Example: add_filter('whx4/events/event_slug', fn() => 'my_event');
        return (string) apply_filters('whx4/events/event_slug', $slug);
    }*/

	// Obsolete? TBC
	public function adjustQueryArgs(array $args, array $params): array
    {
        //error_log( '=== Event::adjustQueryArgs() ===' );
        //error_log( 'args: ' . print_r($args,true) . '; params: ' . print_r($params,true) );

        $dateStart = isset($params['date_start']) ? (string)$params['date_start'] : null;
        $dateEnd   = isset($params['date_end']) ? (string)$params['date_end'] : null;

        // Ensure array shells exist
        $meta = isset($args['meta_query']) && is_array($args['meta_query'])
            ? $args['meta_query']
            : ['relation' => 'AND'];

        $tax  = isset($args['tax_query']) && is_array($args['tax_query'])
            ? $args['tax_query']
            : [];

        // Default: hide past events unless explicitly requested
        if (empty($params['include_past'])) {
            $meta[] = [
                'key'     => 'end_date',
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        // If a date range is provided, use OVERLAP logic:
        // (event.start_date <= range_end) AND (event.end_date >= range_start)
        if ($dateStart || $dateEnd) {
            $rangeStart = $dateStart ?: '0001-01-01';
            $rangeEnd   = $dateEnd   ?: '9999-12-31';

            $meta[] = [
                'key'     => 'start_date',
                'value'   => $rangeEnd,
                'compare' => '<=',
                'type'    => 'DATE',
            ];
            $meta[] = [
                'key'     => 'end_date',
                'value'   => $rangeStart,
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        // Optional category filter via params: event_category=slug or [slugs]
        if (!empty($params['event_category'])) {
            $tax[] = [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => (array)$params['event_category'],
            ];
        }

        // Sensible ordering for events: soonest first, then publish date.
        $args['meta_key']  = 'start_date';
        $args['meta_type'] = 'DATE';
        $args['orderby']   = ['meta_value' => 'ASC', 'date' => 'DESC'];
        $args['order']     = 'ASC';

        $args['meta_query'] = $meta;
        if ($tax) {
            $args['tax_query'] = $tax;
        }

        // Let sites adjust just the Events query if needed.
        return apply_filters('whx4_events_adjusted_query_args', $args, $params);
    }

    public function getDisplayVariants(): array
    {
        return [
            'all'     => ['list','table','archive'],
            'default' => 'list',
            // Optional per-variant options:
            'options' => [
                'table' => ['columns' => ['date','title','category']],
                'list'  => ['show_excerpt' => false],
            ],
        ];
    }
    
    // WIP -- obsolete?
    /*public function getListArgSchema(): array
    {
        return [
            //'type'           => ['type'=>'enum','enum'=>['event'],'default'=>'event'],
            'start'          => ['type'=>'date'],
            'end'            => ['type'=>'date'],
            'event_category' => ['type'=>'string'],
            'orderby'        => ['type'=>'enum','enum'=>['start','title','date'],'default'=>'start'],
            'order'          => ['type'=>'enum','enum'=>['ASC','DESC'],'default'=>'ASC'],
            'paged'          => ['type'=>'int','default'=>get_query_var('paged') ?: 1],
            'per_page'       => ['type'=>'int','default'=>10],
        ];
    }*/

    public function renderItems(array $posts, array $atts, string $variant): string
    {
        return match ($variant) {
            'table'   => $this->renderTable($posts, $atts),
            'archive' => $this->renderArchive($posts, $atts), // e.g., year/month groupings
            default   => $this->renderList($posts, $atts),
        };
    }

    // keep your existing renderList(), plus a simple table renderer…
    private function renderTable(array $posts, array $atts): string
    {
        if (!$posts) {
            return '<div class="whx4-list whx4-list--event is-empty">No events found.</div>';
        }
        $out = '<table class="whx4-list whx4-list--event"><thead><tr><th>Date</th><th>Title</th></tr></thead><tbody>';
        foreach ($posts as $p) {
            $start = get_post_meta($p->ID, 'whx4_events_start', true);
            $out  .= '<tr><td>' . esc_html(date_i18n(get_option('date_format'), strtotime($start))) . '</td>';
            $out  .= '<td><a href="' . esc_url(get_permalink($p)) . '">' . esc_html(get_the_title($p)) . '</a></td></tr>';
        }
        return $out . '</tbody></table>';
    }

    // Deprecated -- Delete?
    public function buildListQueryArgs(array $a): array
    {
        $meta = [];
        if ( $a['start'] ) {
            $meta[] = ['key'=>'whx4_events_start','value'=>$a['start'],'compare'=>'>=','type'=>'DATE'];
        }
        if ( $a['end'] ) {
            $meta[] = ['key'=>'whx4_events_start','value'=>$a['end'],'compare'=>'<=','type'=>'DATE'];
        }

        $tax = [];
        if ( ! empty($a['event_category']) ) {
            $tax[] = [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => sanitize_title($a['event_category']),
            ];
        }

        $orderby = match ($a['orderby']) {
            'start' => ['meta_value' => $a['order']],
            'title' => ['title' => $a['order']],
            default => ['date' => $a['order']],
        };

        return [
            'post_type'      => $this->getSlug(),
            'post_status'    => 'publish',
            'meta_key'       => $a['orderby'] === 'start' ? 'whx4_events_start' : null,
            'meta_query'     => $meta ?: null,
            'tax_query'      => $tax ?: null,
            'orderby'        => key($orderby),
            'order'          => current($orderby),
            //'paged'          => $a['paged'],
            //'posts_per_page' => $a['per_page'],
            //'no_found_rows'  => false,
        ];
    }

    public function renderList(array $posts, array $atts): string
    {
        if ( ! $posts ) {
            return '<div class="whx4-list whx4-list--event is-empty">No events found.</div>';
        }

        $out = '<ul class="whx4-list whx4-list--event">';
        foreach ( $posts as $p ) {
            $start = get_post_meta($p->ID, 'whx4_events_start', true);
            $out  .= '<li><a href="' . esc_url(get_permalink($p)) . '">' . esc_html(get_the_title($p)) . '</a>';
            if ( $start ) {
                $out .= ' <time datetime="' . esc_attr($start) . '">' . esc_html(date_i18n(get_option('date_format'), strtotime($start))) . '</time>';
            }
            $out  .= '</li>';
        }
        $out .= '</ul>';

        return $out;
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
			$summary = new \WXC\Utils\Recurrence\RecurrenceSummaryBuilder( $rrule );
			$text = $summary->getText();

			$field['instructions'] .= '<br><strong>Preview:</strong> ' . esc_html( $text );
		} catch ( \Throwable $e ) {
			// optionally log or ignore
		}

		return $field;
	}
	
	/****** START WIP 02/17/26 ******/
	
	public function expandRecurringInstances(array $posts, \WP_Query $query): array
	{
		error_log('(WHx4) expandRecurringInstances called with ' . count($posts) . ' posts');
		
		error_log('expandRecurringInstances called - is_admin: ' . (is_admin() ? 'yes' : 'no'));
		error_log('is_main_query: ' . ($query->is_main_query() ? 'yes' : 'no'));
		error_log('post_type: ' . $query->get('post_type'));
		error_log('getSlug: ' . $this->getSlug());
		
		// Only process main query on frontend for our post type
		if (is_admin() || !$query->is_main_query() || $query->get('post_type') !== $this->getSlug()) {
			error_log('Conditions failed, returning original posts');
			return $posts;
		}
		
		// Only expand when we have a scope (date filter)
		$scope = get_query_var('scope');
		if (!$scope) {
			return $posts;
		}
		error_log('(WHx4) Scope: ' . $scope);
		
		// Resolve scope to get the target date(s)
		$bounds = ScopedDateResolver::resolve($scope, ['mode' => 'DATE']);
		if (empty($bounds['start']) || empty($bounds['end'])) {
			return $posts;
		}
		
		$until = \DateTimeImmutable::createFromFormat('Y-m-d', $bounds['end']);
		if (!$until) {
			return $posts;
		}
		
		//error_log('(WHx4) Posts: ' . print_r(array_map(fn($p) => ['ID' => $p->ID, 'title' => $p->post_title], $posts), true));

		$expandedPosts = [];
		
		foreach ($posts as $post) {
			// Check if this event is recurring
			if (!InstanceGenerator::isRecurring($post->ID)) {
				// Non-recurring event
				$post->event_is_instance = false;
				$post->event_instance_datetime = \DateTimeImmutable::createFromFormat(
					'Y-m-d',
					get_post_meta($post->ID, self::DATE_META, true)
				);
				$expandedPosts[] = $post;
				continue;
			}
			
			// Get instances within bounds
			$instances = InstanceGenerator::fromPostId($post->ID, 500, false, $until);
			error_log('(WHx4 Event::expandRecurringInstances) Found ' . count($instances) . ' total instances');
			
			// Filter to only instances within bounds
			$instances = array_filter($instances, fn($i) =>
				$i['date_key'] >= $bounds['start'] && $i['date_key'] <= $bounds['end']
			);
			
			error_log('(WHx4 Event::expandRecurringInstances) Found ' . count($instances) . ' instances in scope');
        
			// Clone post for each matching instance
			foreach ($instances as $instance) {
				$clone = clone $post;
				$clone->event_instance_date      = $instance['date_key'];
				$clone->event_instance_datetime  = $instance['datetime'];
				$clone->event_instance_permalink = $instance['permalink'];
				$clone->event_is_instance        = true;
				$clone->event_is_override        = $instance['is_override'];
				$expandedPosts[] = $clone;
			}
			
			// TODO: Step 3 - clone post for each instance
		}
		
		error_log('(WHx4 Event::expandRecurringInstances) Found ' . count($expandedPosts) . ' unsorted expandedPosts');
		
		// Sort by date/time
		usort($expandedPosts, function($a, $b) {
			if (!$a->event_instance_datetime || !$b->event_instance_datetime) {
				return 0;
			}
			return $a->event_instance_datetime <=> $b->event_instance_datetime;
		});
		error_log('(WHx4 Event::expandRecurringInstances) Found ' . count($expandedPosts) . ' *sorted* expandedPosts');
		
		return $expandedPosts;
	}
	
	/****** END WIP ******/

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
	
	// WIP
	
    public function getStartDate(): string
    {
        return (string)$this->getPostMeta('whx4_events_start_date', 'Unknown');
    }
	
	/**
	 * Prepare event info for display
	 * Pre-calculates all view data to keep templates clean
	 * 
	 * @param array $filters Optional filters to pass to ///getTransactions()
	 * @return array Prepared data ready for view rendering
	 */
	public function prepareEventDataForView(array $filters = []): array
	{
		$instances = [];
		
		return [
			'instances' => $instances,
			//'total_count' => $stats['total_count'],
			//'has_data' => !empty($stats['yearly'])
		];
	}
	
	/**
	 * Prepare all data needed for the content view
	 * This keeps the view clean and dependency-free
	 * 
	 * @return array Variables ready for view consumption
	 */
	public function prepareViewData(): array
	{
		// Pre-render the instances list (nested view)
		$instancesListHtml = EventInstances::renderInstancesList($this->post->ID, 50); // TODO: consider different limit? no limit?
		
		return [
			'startDate' => $this->getStartDate(),
			'viewData' => $this->prepareEventDataForView(),
			'postMeta' => $this->getPostMeta(),
			'instancesListHtml' => $instancesListHtml, // Pre-rendered nested view
		];
	}
}
