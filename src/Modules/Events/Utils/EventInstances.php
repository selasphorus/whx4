<?php

namespace atc\WHx4\Modules\Events\Utils;

use WP_Query;
use atc\WXC\Utils\DateHelper;
//use atc\WXC\Utils\RepeaterChangeDetector;
use atc\WXC\Utils\PluginPaths;
use atc\WXC\Templates\ViewLoader;

class EventInstances
{
    public static function register(): void
    {
        add_action( 'add_meta_boxes', [self::class, 'addMetaBox'] );
        add_action( 'edit_form_top', [self::class, 'maybeAddDetachedNotice'] );
        //add_action( 'admin_notices', [self::class, 'maybeShowDetachedCleanupNotice'] );
        //add_action( 'admin_init', [self::class, 'handleDetachedCleanupRequest'] );
        //add_action( 'acf/save_post', [self::class, 'handleExcludedDateRemovals'], 20 );
        add_action( 'admin_enqueue_scripts', [self::class, 'enqueueAdminAssets'] );
        //add_action( 'admin_init', [self::class, 'handleCreateRequest'] );
        //add_action( 'wp_ajax_whx4_create_replacement', [ self::class, 'handleCreateReplacement' ] );
        //add_action( 'wp_ajax_whx4_check_replacement', [ \smith\Rex\Events\Admin\EventInstances::class, 'ajaxCheckReplacement' ] );
        
        // Add routing for individual instances
		add_action( 'init', [self::class, 'addRewriteRules'] );
		add_filter( 'query_vars', [self::class, 'addQueryVars'] ); // TODO: standardize addition of query vars across WXC ecosystem
		add_action( 'pre_get_posts', [self::class, 'modifyMainQuery'] );
		add_filter( 'template_include', [self::class, 'loadInstanceTemplate'] );
    }

    /**
     * Add meta box to event edit screen.
     */
    public static function addMetaBox(): void
    {
        add_meta_box(
            'whx4_event_exclusions_box',
            'Recurring Event Instances',
            [self::class, 'renderMetaBox'],
            'whx4_event', //'event',
            'side',
            'default'
        );
    }
    
    /**
	 * Get instances data for display.
	 * 
	 * @param int  $postID      The parent event post ID.
	 * @param int  $limit       Maximum number of instances to generate.
	 * @param bool $includeInfo Whether to include generation info.
	 * @return array|null Array with instances data, or null if not recurring.
	 */
	protected static function getInstancesData( int $postID, int $limit = 50, bool $includeInfo = false ): ?array
	{
		// Check if this is a recurring event
		if ( ! InstanceGenerator::isRecurring( $postID ) ) {
			return null;
		}
	
		// Generate instances
		$instances = InstanceGenerator::fromPostId( $postID, $limit, $includeInfo );
		
		// Get excluded dates
		$excluded = maybe_unserialize( get_post_meta( $postID, 'whx4_events_excluded_dates', true ) ) ?: [];
		if ( ! is_array( $excluded ) ) {
			$excluded = [];
		}
	
		// Get replacements map
		$replacements = self::getReplacementPostIds( $postID );
	
		return [
			'post_id'      => $postID,
			'instances'    => $instances,
			'excluded'     => $excluded,
			'replacements' => $replacements,
		];
	}
	
	/**
	 * Render the instances meta box (admin).
	 * 
	 * @param \WP_Post $post The post object.
	 */
	public static function renderMetaBox( \WP_Post $post ): void
	{
		$data = self::getInstancesData( $post->ID, 50, true );
		
		if ( $data === null ) {
			echo '<p>This event does not have recurrence rules.</p>';
			return;
		}
	
		ViewLoader::render(
			'event-instances-columnar-list',
			$data,
			[ 'kind' => 'partial', 'module' => 'events', 'post_type' => 'event' ]
		);
	}
	
	/**
	 * Render instances list for frontend display.
	 * 
	 * @param int   $postID The parent event post ID.
	 * @param int   $limit  Maximum number of instances to show. Default 20.
	 * @param array $args   Additional arguments for the view.
	 * @return string       HTML output, or empty string if not recurring.
	 */
	public static function renderInstancesList( int $postID, int $limit = 20, array $args = [] ): string
	{
		$data = self::getInstancesData( $postID, $limit, false );
		
		if ( $data === null ) {
			return '';
		}
	
		// Merge any additional args
		$data = array_merge( $data, $args );
	
		return ViewLoader::renderToString(
			'event-instances-list',
			$data,
			[ 'kind' => 'partial', 'module' => 'events', 'post_type' => 'event' ]
		);
	}
	
	/**
	 * Display instances list for frontend (echo version).
	 * 
	 * @param int   $postID The parent event post ID.
	 * @param int   $limit  Maximum number of instances to show. Default 20.
	 * @param array $args   Additional arguments for the view.
	 */
	public static function displayInstancesList( int $postID, int $limit = 20, array $args = [] ): void
	{
		echo self::renderInstancesList( $postID, $limit, $args );
	}

    /**
     * Get override dates for a parent event (for InstanceGenerator).
     * Returns map of date_key => ['datetime' => DateTimeInterface, 'post_id' => int]
     *
     * @param  int   $parent_id
     * @return array
     */
    public static function getOverrideDates( int $parent_id ): array
    {
        $args = [
            'post_type'      => 'whx4_event',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'whx4_events_detached_from',
                    'value'   => $parent_id,
                    'compare' => '=',
                ],
                [
                    'key'     => 'whx4_events_detached_date',
                    'compare' => 'EXISTS',
                ],
            ],
            'fields' => 'ids',
        ];

        $replacements = get_posts( $args );
        $map = [];

        foreach ( $replacements as $postID ) {
            $original = get_post_meta( $postID, 'whx4_events_detached_date', true );
            //$start    = get_field( 'whx4_events_start_date', $postID );
            $startDT = DateHelper::combineDateAndTime(
                get_post_meta( $postID, 'whx4_events_start_date', true ),
                get_post_meta( $postID, 'whx4_events_start_time', true )
            );

            if ( $original && $startDT instanceof \DateTimeInterface ) {
                $map[ $original ] = [
                    'datetime' => $startDT,
                    'post_id'  => $postID,
                ];
            }
        }

        return $map;
    }

    /**
     * Get simple map of date_key => replacement_post_id (for admin UI).
     *
     * @param  int   $parent_id
     * @return array
     */
    public static function getReplacementPostIds( int $parent_id ): array
    {
        $overrides = self::getOverrideDates( $parent_id );
        $map = [];
        
        foreach ( $overrides as $dateKey => $data ) {
            $map[ $dateKey ] = $data['post_id'];
        }
        
        return $map;
    }

    public static function getInstanceDivHtml( int $postID, string $date ): string
    {
        ob_start();

        // You can reuse a template part or include logic here directly.
        // Example: template partial to render a single instance row
        $context = [
            'post_id' => $postID,
            'date'    => $date,
            'actions' => self::getAvailableActions( $postID, $date ), // if needed
        ];

        $vars = [
            'post_id'     => $postID,
            'instances'   => $instances,
            'excluded'    => $excluded,
            //'replacements'=> $replacements,
        ];

        ViewLoader::render(
            'event-instance-div',
            $vars,
            [ 'kind' => 'partial', 'module' => 'events', 'post_type' => 'event' ] // specs
        );

        return ob_get_clean();
    }


    public static function handleCreateRequest(): void
    {
        if (
            ! isset( $_GET['whx4_create_detached_event'], $_GET['event_id'], $_GET['date'], $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( $_GET['_wpnonce'], 'whx4_create_detached_event' )
        ) {
            return;
        }

        $event_id = (int) $_GET['event_id'];
        $date = sanitize_text_field( $_GET['date'] );

        if ( self::replacementExists( $event_id, $date ) ) {
            wp_safe_redirect( admin_url( 'edit.php?post_type=whx4_event' ) ); // eventually: post_type=event (after EM phase-out)
            exit;
        }

        $original = get_post( $event_id );
        if ( ! $original instanceof \WP_Post || $original->post_type !== 'whx4_event' ) { // 'event'
            return;
        }

        $clone_id = wp_insert_post([
            'post_type' => 'whx4_event', //'event',
            'post_status' => 'draft',
            'post_title' => $original->post_title . ' (' . $date . ')',
            'post_content' => $original->post_content,
        ]);

        $meta = get_post_meta( $event_id );
        foreach ( $meta as $key => $values ) {
            if (
                str_starts_with( $key, 'whx4_events_rrule' )
                || str_starts_with( $key, 'whx4_events_recurrence' )
                || str_starts_with( $key, 'whx4_events_excluded_dates' )
                ) {
                continue;
            }
            /*if ( in_array( $key, [ 'whx4_events_rrule', 'whx4_events_excluded_dates' ], true ) ) {
                continue;
            }*/

            foreach ( $values as $val ) {
                update_post_meta( $clone_id, $key, maybe_unserialize( $val ) );
            }
        }

        // Update date to exclusion date
        update_post_meta( $clone_id, 'whx4_events_start_date', $date );

        // Mark this as a detached instance
        update_post_meta( $clone_id, 'whx4_events_detached_from', $event_id );
        update_post_meta( $clone_id, 'whx4_events_detached_date', $date );

        // Ensure it's not treated as recurring
        update_post_meta( $clone_id, 'whx4_events_is_recurring', 0 );

        wp_safe_redirect( get_edit_post_link( $clone_id, '' ) );
        exit;
    }

    //public static function getDetachedPostId( int $parent_id, string $dateStr ): ?int
    protected static function replacementExists( int $parent_id, string $dateStr ): bool
    {
        $query = new WP_Query([
            'post_type' => 'whx4_event', //'event',
            'post_status' => [ 'publish', 'draft', 'pending' ],
            'meta_query' => [
                [
                    'key' => 'whx4_events_detached_from',
                    'value' => $parent_id,
                    //'compare' => '='
                ],
                [
                    'key' => 'whx4_events_detached_date',
                    'value' => $dateStr,
                    //'compare' => '='
                ],
            ],
            'fields' => 'ids',
        ]);

        return ! empty( $query->posts );
        //return $query->have_posts() ? (int) $query->posts[0] : null;
    }

    public static function maybeAddDetachedNotice( \WP_Post $post ): void
    {
        if ( $post->post_type !== 'whx4_event' ) {
            return;
        }

        $original_id = get_post_meta( $post->ID, 'whx4_events_detached_from', true );

        if ( ! $original_id ) {
            return;
        }

        $url = get_edit_post_link( $original_id, '' );

        echo '<div class="notice notice-info"><p>';
        echo 'This is a <strong>replacement</strong> for a recurring event on <strong>' .
             esc_html( get_post_meta( $post->ID, 'whx4_events_detached_date', true ) ) . '</strong>. ';
        echo 'View original: <a href="' . esc_url( $url ) . '">Event #' . esc_html( $original_id ) . '</a>';
        echo '</p></div>';
    }

    // Revise or remove -- no longer using repeater rows for exclusions
    public static function handleExcludedDateRemovals( $postID ): void
    {
        error_log( '=== class: EventInstances; method: handleExcludedDateRemovals ===' );

        if ( get_post_type( $postID ) !== 'whx4_event' ) {
            return;
        }

        $removed = RepeaterChangeDetector::detectRemovedValues(
            $postID,
            'whx4_events_excluded_dates',
            'whx4_events_exdate_date'
        );
        error_log( 'removed: ' . print_r($removed,true) );

        $pending = [];

        foreach ( $removed as $date ) {
            if ( self::replacementExists( $postID, $date ) ) {
                $pending[] = $date;
            }
        }
        error_log( 'pending: ' . print_r($pending,true) );

        if ( $pending ) {
            set_transient( "whx4_events_cleanup_{$postID}", $pending, 600 );
        }
    }

    public static function maybeShowDetachedCleanupNotice(): void
    {
        global $post;

        if ( ! $post || get_post_type( $post ) !== 'whx4_event' ) {
            return;
        }

        $dates = get_transient( "whx4_events_cleanup_{$post->ID}" );
        if ( ! $dates ) {
            return;
        }

        delete_transient( "whx4_events_cleanup_{$post->ID}" );

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Detached replacement events exist for removed exclusions:</strong></p><ul>';

        foreach ( $dates as $date ) {
            $trash_url = wp_nonce_url( add_query_arg([
                'whx4_trash_detached' => 1,
                'event_id' => $post->ID,
                'date' => $date,
            ]), 'whx4_trash_detached_event', '_wpnonce' );

            echo '<li>' . esc_html( $date ) . ' ';
            echo '<a href="' . esc_url( $trash_url ) . '" class="button button-small">Trash Replacement</a>';
            echo '</li>';
        }

        echo '</ul></div>';
    }

    public static function handleDetachedCleanupRequest(): void
    {
        if (
            ! isset( $_GET['whx4_trash_detached'], $_GET['event_id'], $_GET['date'], $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( $_GET['_wpnonce'], 'whx4_trash_detached_event' )
        ) {
            return;
        }

        $event_id = (int) $_GET['event_id'];
        $date = sanitize_text_field( $_GET['date'] );

        $query = new \WP_Query([
            'post_type' => 'event',
            'post_status' => [ 'publish', 'draft', 'pending' ],
            'meta_query' => [
                [
                    'key' => 'whx4_events_detached_from',
                    'value' => $event_id,
                ],
                [
                    'key' => 'whx4_events_detached_date',
                    'value' => $date,
                ],
            ],
            'fields' => 'ids',
        ]);

        foreach ( $query->posts as $postID ) {
            wp_trash_post( $postID );
        }

        wp_safe_redirect( admin_url( 'post.php?post=' . $event_id . '&action=edit' ) );
        exit;
    }
    
    /**
     * Enqueue admin assets for event edit screen.
     */
    public static function enqueueAdminAssets(): void
    {
        global $post;

        if ( ! $post || get_post_type( $post ) !== 'whx4_event' ) {
            return;
        }

        wp_enqueue_style(
            'whx4-event-admin-styles',
            WHX4_PLUGIN_URL . 'src/Modules/Events/Assets/whx4-events-admin.css',
            //PluginPaths::url( 'src/Modules/Events/Assets/whx4-events-admin.css' ),
            [],
            //filemtime( PluginPaths::url( 'src/Modules/Events/Assets/whx4-events-admin.css' ), )
        );

        wp_enqueue_script(
            'whx4-event-overrides',
            WHX4_PLUGIN_URL . 'src/Modules/Events/Assets/event-overrides.js',
            //PluginPaths::url( 'src/Modules/Events/Assets/event-overrides.js' ),
            [ 'jquery' ],
            '1.0',
            true
        );

        wp_enqueue_script(
            'whx4-events-admin',
            WHX4_PLUGIN_URL . 'src/Modules/Events/Assets/whx4-events-admin.js',
            //PluginPaths::url( 'src/Modules/Events/Assets/whx4-events-admin.js' ),
            //plugins_url( '/assets/js/whx4-events-admin.js', __FILE__ ),
            [],
            '1.0',
            true
        );

        wp_localize_script( 'whx4-events-admin', 'whx4EventsAjax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'whx4_events_nonce' ),
        ]);
    }

    /**
     * Create a replacement/detached event for a specific instance.
     *
     * @param  int    $parent_id   The recurring event ID
     * @param  string $dateKey     The date being replaced (Y-m-d format)
     * @return int|false           New post ID or false on failure
     */
    public static function createReplacement( int $parent_id, string $dateKey )
    {
        // Check if replacement already exists
        if ( self::replacementExists( $parent_id, $dateKey ) ) {
            return false;
        }

        $original = get_post( $parent_id );
        if ( ! $original instanceof \WP_Post || $original->post_type !== 'whx4_event' ) {
            return false;
        }

        // Create the clone
        $clone_id = wp_insert_post([
            'post_type'    => 'whx4_event',
            'post_status'  => 'draft',
            'post_title'   => $original->post_title . ' (' . $dateKey . ')',
            'post_content' => $original->post_content,
        ]);

        if ( ! $clone_id ) {
            return false;
        }

        // Copy all meta except recurrence-related fields
        $meta = get_post_meta( $parent_id );
        $skip_keys = [
            'whx4_events_rrule',
            'whx4_events_is_recurring',
            'whx4_events_excluded_dates',
        ];

        foreach ( $meta as $key => $values ) {
            if ( in_array( $key, $skip_keys, true ) ) {
                continue;
            }

            foreach ( $values as $val ) {
                update_post_meta( $clone_id, $key, maybe_unserialize( $val ) );
            }
        }

        // Update the start date to the instance date
        update_post_meta( $clone_id, 'whx4_events_start_date', $dateKey );

        // Mark as detached/replacement
        update_post_meta( $clone_id, 'whx4_events_detached_from', $parent_id );
        update_post_meta( $clone_id, 'whx4_events_detached_date', $dateKey );
        update_post_meta( $clone_id, 'whx4_events_is_recurring', 0 );

        return $clone_id;
    }
    
    ///
    
    /**
	 * Add rewrite rules for events and date-prefixed event instances.
	 */	
	public static function addRewriteRules(): void
	{
		// Date-prefixed individual instances: /event/2026-01-22-test-event/
		add_rewrite_rule(
			'whx4_event/([0-9]{4}-[0-9]{2}-[0-9]{2})-([^/]+)/?$',
			'index.php?post_type=whx4_event&name=$matches[2]&event_instance=$matches[1]',
			'top'
		);
		
		// Date-based archives: /events/2026-01-22/
		add_rewrite_rule(
			'whx4_calendar/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$',
			'index.php?post_type=whx4_event&scope=$matches[1]',
			'top'
		);
	}
	
	/**
	 * Register custom query var for event instance date.
	 */
	public static function addQueryVars( $vars ): array
	{
		$vars[] = 'scope';
		$vars[] = 'event_instance';
		return $vars;
	}
	
	/**
	 * Modify main query for instance views.
	 */
	public static function modifyMainQuery( $query ): void
	{
		if ( ! is_admin() && $query->is_main_query() ) {
			$instance_date = get_query_var( 'event_instance' );
			
			if ( $instance_date && $query->get( 'post_type' ) === 'whx4_event' ) {
				$query->set( 'posts_per_page', 1 );
			}
		}
	}
	
	/**
	 * Load appropriate template and validate instance.
	 */
	public static function loadInstanceTemplate( $template )
	{
		$instance_date = get_query_var( 'event_instance' );
		
		if ( ! $instance_date || ! is_singular( 'whx4_event' ) ) {
			return $template;
		}
		
		global $post;
		
		// Validate this date is a real instance
		$instance = InstanceGenerator::getSingleInstance( $post->ID, $instance_date );
		
		if ( ! $instance ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return get_404_template();
		}
		
		// Store instance data for template use
		$post->current_instance = $instance;
		
		// Optionally use a different template
		$instance_template = locate_template( ['single-whx4_event-instance.php'] );
		if ( $instance_template ) {
			return $instance_template;
		}
		
		return $template;
	}
}