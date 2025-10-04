<?php

namespace atc\WHx4\Core;
// TODO: move this and BaseHandler to atc\WHx4\Core\Handlers\ ?

use atc\WHx4\Core\WHx4;
use atc\WHx4\Core\BaseHandler;
use atc\WHx4\Core\Traits\AppliesTitleArgs;
use atc\WHx4\Core\Query\PostQuery;
//
use atc\WHx4\Utils\ClassInfo;

abstract class PostTypeHandler extends BaseHandler
{
	use AppliesTitleArgs;

	// Property to store the post object
    protected ?\WP_Post $post = null; //protected $post; // better private?
    protected const TYPE = 'post_type';

    /** @var array<string,string> Cache: post_type => handler FQCN */
    protected static array $handlerClassCache = [];

    /** @var array<int,self> Cache: post_id => handler instance */
    protected static array $perPostCache = [];

    // Constructor to set the config and post object
    /*public function __construct( array $config = [], ?\WP_Post $post = null )
    {
        parent::__construct( $config, $post );
    }*/
    // Constructor
	public function __construct(array $config = [], ?\WP_Post $post = null)
	{
		parent::__construct($config, $post);
		$this->post = $post;
	}

    public function boot(): void
	{
        add_filter( 'the_content', [ self::class, 'appendCustomContent' ], 15 );
	}

	// Optional explicit setter (handy for guarantees/safety-net)
	// TBD: is this still needed? Redundant w/ constructor...
	public function setPost(?\WP_Post $post): static
	{
		$this->post = $post;
		return $this;
	}

	public function getPost(): ?\WP_Post
	{
		return $this->post;
	}

	 /**
	 * Optional spec hook. Child classes override this only if they need
	 * custom query behavior (date ranges, CPT-specific defaults, etc.).
	 *
	 * Expected keys if provided:
	 * - 'cpt' (string)
	 * - 'date_meta' => ['key' OR 'start_key'+'end_key', 'meta_type' => 'DATE'|'DATETIME'|'NUMERIC']
	 * - 'taxonomies' => [ 'event_category', ... ]
	 * - 'defaults'   => ['limit','order','orderby','view']
	 * - 'allowed_orderby' => [...]
	 * - 'default_view'    => 'list'|'grid'|'table'
	 */
	protected static function getQuerySpec(): array
	{
		return [];
	}

    public static function queryDefaults(): array
    {
        error_log( "PostTypeHandler::queryDefaults" );
        $spec = static::getQuerySpec();
        $ptype = $spec['cpt'] ?? (static::resolvePostTypeFromContext() ?? '');
        //
        if ( isset($spec['cpt']) ) { error_log( "spec['cpt']: " . $spec['cpt'] ); } else { error_log( "spec['cpt'] not set" ); }
        error_log( "ptype: " . $ptype );

        $defaults = array_merge([
            'post_type'      => $ptype,
            'post_status'    => 'publish',
            'view'           => $spec['default_view'] ?? 'list',
            'limit'          => $spec['defaults']['limit']  ?? 10,
            'order'          => $spec['defaults']['order']  ?? 'ASC',
            'orderby'        => $spec['defaults']['orderby']?? 'meta_value',
            'scope'          => '',
            'paged'          => '',
        ], self::taxonomyDefaultInputs($spec));

        /** @var array $filtered */
        $filtered = apply_filters('whx4_generic_query_defaults', $defaults, $spec);
        return $filtered;
    }

    protected static function resolvePostTypeFromContext(): ?string
	{
		error_log( "PostTypeHandler::resolvePostTypeFromContext" );
		try {
			$ctx = WHx4::ctx();
			$map = is_array($ctx->getActivePostTypes()) ? $ctx->getActivePostTypes() : [];
			foreach ($map as $ptype => $class) {
				if ($class === static::class) {
					error_log( "resolvePostTypeFromContext returning ptype: " . $ptype );
					return (string) $ptype;
				}
			}
		} catch (\Throwable $e) {
			// ignore; return null
		}
		return null;
	}


    public static function normalizeFilters(array $input): array
    {
        $spec = static::getQuerySpec();
        $in = array_merge(static::queryDefaults(), $input);

        $qv = (int) get_query_var('paged');
        $paged = $in['paged'] !== '' ? (int) $in['paged'] : ($qv > 0 ? $qv : 1);
        if ($paged < 1) { $paged = 1; }

        $order = strtoupper((string) $in['order']);
        if (!in_array($order, ['ASC','DESC'], true)) { $order = 'ASC'; }

        $allowedOrderby = $spec['allowed_orderby'] ?? ['meta_value','date','title','menu_order','modified'];
        $orderby = (string) $in['orderby'];
        if (!in_array($orderby, $allowedOrderby, true)) {
            $orderby = $spec['defaults']['orderby'] ?? 'meta_value';
        }

        // Scope (string) or explicit {start,end}
        $scope = null;
        if ($in['scope'] !== '') {
            $scope = (string) $in['scope'];
        } elseif (($in['start_date'] ?? '') !== '' || ($in['end_date'] ?? '') !== '') {
            $scope = [
                'start' => $in['start_date'] !== '' ? (string) $in['start_date'] : null,
                'end'   => $in['end_date']   !== '' ? (string) $in['end_date']   : null,
            ];
        }

        // Taxonomy inputs: accept CSV per taxonomy key
        $taxInputs = self::parseTaxInputs($spec, $in);

        $normalized = [
            'post_type'   => (string) $in['post_type'],
            'post_status' => (string) $in['post_status'],
            'view'        => in_array($in['view'], ['list','grid','table'], true) ? $in['view'] : ($spec['default_view'] ?? 'list'),
            'limit'       => max(1, (int) $in['limit']),
            'order'       => $order,
            'orderby'     => $orderby,
            'scope'       => $scope,
            'tax_inputs'  => $taxInputs, // map: taxonomy => [slugs]
            'paged'       => $paged,
        ];

        /** @var array $filtered */
        $filtered = apply_filters('whx4_generic_normalize_filters', $normalized, $input, $spec);
        return $filtered;
    }

    public static function buildQueryParams(array $filters): array
    {
        $spec = static::getQuerySpec();
        $tax = [];
        foreach (($filters['tax_inputs'] ?? []) as $taxonomy => $slugs) {
            if (!empty($slugs)) {
                $tax[$taxonomy] = $slugs;
            }
        }

        // Date meta spec: either single 'key' or start/end keys
        $dateMeta = $spec['date_meta'] ?? [];
        $metaKeyForSort = $filters['orderby'] === 'meta_value'
            ? ($dateMeta['key'] ?? $dateMeta['start_key'] ?? null)
            : null;

        $params = [
            'post_type'      => $filters['post_type'],
            'post_status'    => $filters['post_status'],
            'paged'          => $filters['paged'],
            'posts_per_page' => $filters['limit'],
            'order'          => $filters['order'],
            'orderby'        => $filters['orderby'],
            'meta_key'       => $metaKeyForSort,
            'date_meta'      => $dateMeta ?: null,
            'scope'          => $filters['scope'],
            'tax'            => $tax,
        ];

        /** @var array $filtered */
        $filtered = apply_filters('whx4_generic_query_params', $params, $filters, $spec);
        return $filtered;
    }

    public static function find(array $filters): array
    {
        $normalized = static::normalizeFilters($filters);
        $params = static::buildQueryParams($normalized);

        $query  = new PostQuery();
        $result = $query->find($params);

        $payload = [
            'posts'      => $result['posts'] ?? [],
            'pagination' => [
                'found'     => $result['found']     ?? 0, // maybe move this up a level? i.e. not part of pagination array
                'max_pages' => $result['max_pages'] ?? 0,
                'paged'     => $normalized['paged'],
            ],
        ];

        //if (defined('WHX4_DEBUG') && WHX4_DEBUG) {
            $payload['debug'] = [
                'args'          => $result['args']          ?? [],
                'query_request' => $result['query_request'] ?? '',
                'params'        => $params,
                'filters'       => $normalized,
            ];
        //}

        /** @var array $filtered */
        //$filtered = apply_filters('whx4_generic_result', $payload, $params, $normalized, static::getQuerySpec());
        $filtered = $payload; // tft
        return $filtered;
    }

    /** @internal: helper to build empty tax inputs from spec */
    protected static function taxonomyDefaultInputs(array $spec): array
    {
        $out = [];
        foreach ($spec['taxonomies'] ?? [] as $tax) {
            $out[$tax] = '';
        }
        // Also allow optional start_date/end_date passthrough for explicit windows
        $out['start_date'] = '';
        $out['end_date'] = '';
        return $out;
    }

    /** @internal: turn CSV strings into arrays per taxonomy key */
    protected static function parseTaxInputs(array $spec, array $in): array
    {
        $map = [];
        foreach ($spec['taxonomies'] ?? [] as $tax) {
            $raw = isset($in[$tax]) ? (string) $in[$tax] : '';
            if ($raw === '') {
                $map[$tax] = [];
                continue;
            }
            $map[$tax] = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        return $map;
    }


	public static function allowedUrlParams(): array { return []; }

    public function getCapType(): array
    {
        $capType = $this->getConfig()['capability_type'] ?? [];
        if ( empty($capType) ) { $capType = [ $this->getSlug(), $this->getPluralSlug() ]; } else if ( !is_array($capType) ) { $capType = [$capType, "{$capType}s" ]; };
        return $capType;
        //return $this->getConfig()['capability_type'] ?? [ $this->getSlug(), $this->getPluralSlug() ];
    }

    public function getSupports(): array
    {
        return $this->getConfig()['supports'] ?? [ 'title', 'editor' ];
    }

    public function getTaxonomies(): array
    {
        //$taxonomies = $this->getConfig()['taxonomies'] ?? [ 'admin_tag' => 'AdminTag' ];
        return $this->getConfig()['taxonomies'] ?? [ 'admin_tag' ];
        // WIP 08/26/25 -- turn this into an array of slug -> className pairs
        //return $taxonomies;
        //// WIP 08/26/25 -- figure out how to get fqcn for bare class names

        // Wherever you attach/ensure taxonomies, resolve them:
        //$taxonomyClasses = $this->resolveTaxonomyClasses($this->getConfig('taxonomies') ?? []);
        // Example: hand them to your registrar, or call static register() if you use handlers.
        // $this->taxonomyRegistrar->ensureRegistered($taxonomyClasses);

        //return $this->getConfig()['taxonomies'] ?? [ 'admin_tag' => 'AdminTag' ];
        //return $taxonomyClasses;
    }

    public function getMenuIcon(): ?string
    {
        return $this->getConfig()['menu_icon'] ?? 'dashicons-superhero';
    }


    /**
     * Get the handler FQCN for a CPT slug, or null if not WHx4-managed.
     */
    public static function getHandlerClassForPostType(string $postType): ?string
    {
        if (isset(self::$handlerClassCache[$postType])) {
            return self::$handlerClassCache[$postType];
        }

        $activePostTypeSlugs = (array) apply_filters('whx4_active_post_types', []);

        if ( !in_array($postType, $activePostTypeSlugs, true) ) {
            return null;
        }

        $activePostTypes = WHx4::ctx()->getActivePostTypes(); // ['person' => \...Person::class]
        if ( empty( $activePostTypes ) ) {
			return null;
		}

        $class = $activePostTypes[$postType] ?? null;

        if (is_string($class) && class_exists($class)) {
            self::$handlerClassCache[$postType] = $class;
            return $class;
        }

        return null;
    }

    /**
     * Get the handler instance for a post (or current global $post).
     * Returns a concrete subclass of PostTypeHandler, cached per post ID.
     */
    //public static function getHandlerForPost(\WP_Post|int|null $post = null): ?self
    public static function getHandlerForPost(\WP_Post $post): ?static
    {
        // Normalize $post
		if ($post === null) {
			$post = get_post();
		} elseif (is_int($post)) {
			$post = get_post($post);
		}
		if (!$post instanceof \WP_Post) {
			return null;
		}

        // Per-post cache
        $pid = (int) $post->ID;
        if (isset(self::$perPostCache[$pid])) {
            return self::$perPostCache[$pid];
        }

        // Resolve handler class for this CPT
        $pt = $post->post_type ?: get_post_type($post);
		if (!$pt) {
			return null;
		}

        $class = self::getHandlerClassForPostType($pt);
        if (!$class || !class_exists($class)) {
			return null;
		}

        // Handlers in WHx4 accept (?\WP_Post $post = null)
        /** @var self $instance */
        $instance = new $class($post);

        // Safety-net: force the post onto the instance
		if (method_exists($instance, 'setPost')) {
			$instance->setPost($post);
		}

        return self::$perPostCache[$pid] = $instance;
    }

    ///

    public function getPostId(): int
	{
		return $this->post instanceof \WP_Post ? (int) $this->post->ID : 0;
	}
    /**
	 * Get the post ID, optionally for a provided post.
	 */
	/*public function getPostId(?\WP_Post $post = null): ?int
	{
		$p = $post ?? self::getPost();
		//$p = $post ?? $this->getPost();
		return $p ? (int)$p->ID : null;
	}*/

	/**
	 * Get post meta. If $key is null, returns all meta (array).
	 * If $key is provided, returns get_post_meta($id, $key, $single).
	 * Returns [] (no key) or null (with key) when no post is set.
	 */
	public function getPostMeta(?string $key = null, mixed $default = null): mixed
	{
		$id = $this->getPostId();
		if ($id <= 0) {
			return $default;
		}

		if ($key === null) {
			// Return all meta for this post
			return get_post_meta($id);
		}

		$val = get_post_meta($id, $key, true);
		return ($val === '' || $val === null) ? $default : $val;
	}


    // Method to get the post title
    /*public function get_post_title()
    {
        return get_the_title($this->getPostID());
    }*/

    //public function getCustomTitleArgs(): array
	public function getCustomTitleArgs( \WP_Post $post ): array
	{
		return [];
	}

	// WIP -- maybe this goes elsewhere?
	function getRelatedPosts( $args = [] )
	{
		// Defaults
		$defaults = array(
			'post_id'           => null,
			'related_post_type' => null,
			'related_field_name'=> null,
			'limit'             => "-1",
			'scope'             => null,
		);
		$args = wp_parse_args( $args, $defaults );
		// TBD: use extract? maybe not as safe, though
		$post_id = $args['post_id'];
		$related_post_type = $args['related_post_type'];
		$related_field_name = $args['related_field_name'];
		$limit = $args['limit'];
		$scope = $args['scope'];
		//
		$arrPosts = [];

		// If we don't have actual values for all parameters, there's not enough info to proceed
		if ($post_id === null || $related_field_name === null || $related_post_type === null) { return null; }

		$related_id = null; // init

		// Set args
		$wp_args = array(
			'post_type'   => $related_post_type,
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'meta_query' => array(
				array(
					'key'     => $related_field_name,
					'value'   => $post_id,
				)
			),
			'orderby'        => 'title',
			'order'            => 'ASC',
		);

		// Run query
		$related = new WP_Query( $wp_args );

		// Loop through the records returned
		if ( $related->posts && count($related->posts) > 0 ) {

			return $related->posts;
			/*
			if ( $limit == 1 ) {
				$p = $related->posts[0];
				$info = $p->ID; // ok?
			} else {
				$info = $related_posts->posts;
			}
			*/
			/*
			$info .= "<br />";
			//$info .= "related_posts: ".print_r($related_posts,true);
			$info .= "related_posts->posts:<pre>".print_r($related_posts->posts,true)."</pre>";
			$info .= "wp_args:<pre>".print_r($wp_args,true)."</pre>";
			*/

		} else {
			//$info = "No matching posts found for wp_args: ".print_r($wp_args,true);
		}

		return $arrPosts;
	}

	// TODO: modify to allow for before/after/replace of $content with custom content(?)
	public static function appendCustomContent( string $content ): string
	{
	    $post = get_post();
	    $postType = get_post_type();

		if ( ! is_singular( $postType ) || ! in_the_loop() || ! is_main_query()  || !$post instanceof \WP_Post) {
			return $content;
		}

        $handlerClass = self::getHandlerClassForPostType($postType);
        $module = strtolower((string) ClassInfo::getModuleKey($handlerClass));

        $extra = ViewLoader::renderToString( 'content',
            // vars
            [ 'post' => $post ],
            // specs
            [ 'kind' => 'partial', 'module' => $module, 'post_type' => $postType ]
        );

        // Render your CPT-specific template part via ViewLoader (cascade: child theme > parent theme > plugin)
        /*$extra = ViewLoader::render( '{$postType}/content', [
            'post' => get_post(),
        ] );*/

        return $content . $extra;
	}

	//public function getCustomContent(\WP_Post $post): string
	public function getCustomContent()
	{
		$post_id = $this->getPostID();

		// This function retrieves supplementary info -- the regular content template (content.php) handles title, content, featured image

		// Init
		$info = "";
		$ts_info = "";

		$ts_info .= "post_id: ".$post_id."<br />";

		if ( $post_id === null ) { return false; }

		$info .= $ts_info;

		return $info;

	}
}

