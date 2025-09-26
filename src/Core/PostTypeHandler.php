<?php

namespace atc\WHx4\Core;

use WP_Post;
use atc\WHx4\Core\WHx4;
use atc\WHx4\Core\BaseHandler;
use atc\WHx4\Core\Traits\AppliesTitleArgs;
//
use atc\WHx4\Utils\ClassInfo;

abstract class PostTypeHandler extends BaseHandler
{
	use AppliesTitleArgs;

	// Property to store the post object
    protected $post; // better private?
    protected const TYPE = 'post_type';

    // WIP
    /** @var array<string,string> Cache: post_type => handler FQCN */
    protected static array $handlerClassCache = [];

    /** @var array<int,self> Cache: post_id => handler instance */
    protected static array $perPostCache = [];
    // END WIP

    // Constructor to set the config and post object
    public function __construct( array $config = [], WP_Post|null $post = null )
    {
        parent::__construct( $config, $post );
    }

    public function boot(): void
	{
        add_filter( 'the_content', [ self::class, 'appendCustomContent' ], 15 );
	}

	public function getPost(): ?\WP_Post
	{
		return $this->object instanceof \WP_Post ? $this->object : null;
	}

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

    //// WIP

    /**
     * Get the handler FQCN for a CPT slug, or null if not Rex-managed.
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
    //public static function getHandlerForPost(WP_Post|int|null $post = null): ?self
    public static function getHandlerForPost(WP_Post $post): ?self
    {
        // Normalize $post
        if ($post === null) {
            $post = get_post();
            if (!$post instanceof WP_Post) {
                return null;
            }
        } elseif (is_int($post)) {
            $post = get_post($post);
            if (!$post instanceof WP_Post) {
                return null;
            }
        }

        // Per-post cache
        $pid = (int) $post->ID;
        if (isset(self::$perPostCache[$pid])) {
            return self::$perPostCache[$pid];
        }

        // Resolve handler class for this CPT
        $pt = $post->post_type; //get_post_type($post);
        if (!$pt) {
            return null;
        }

        $class = self::getHandlerClassForPostType($pt);
        if (!$class) {
            return null;
        }

        // Handlers in Rex accept (WP_Post|null $post = null)
        /** @var self $instance */
        $instance = new $class($post);

        return self::$perPostCache[$pid] = $instance;
    }

    ///

    // Method to get the post ID
    public function getPostID()
    {
        return $this->getPost()->ID;
    }

    public function getPostMeta()
    {
        // WIP 09/22/25
    }

    // Method to get the post title
    public function get_post_title()
    {
        return get_the_title($this->getPostID());
    }

    //public function getCustomTitleArgs(): array
	public function getCustomTitleArgs( \WP_Post $post ): array
	{
		return [];
	}

	//
	// TODO: add 'scope' parameter
	function getRelatedPosts( $post_id = null, $related_post_type = null, $related_field_name = null, $limit = '-1' )
	{ // $single = false

		$arrPosts = []; // init

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
					'value'   => $post_id
				)
			),
			'orderby'        => 'title',
			'order'            => 'ASC',
		);

		// Run query
		$related = new WP_Query( $wp_args );

		// Loop through the records returned
		if ( $related->posts ) {

			return $related_posts->posts;
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

	//
	public static function appendCustomContent( string $content ): string
	{
	    $post = get_post();
	    $postType = get_post_type();

		if ( ! is_singular( $postType ) || ! in_the_loop() || ! is_main_query()  || !$post instanceof WP_Post) {
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

	//public function getCustomContent(WP_Post $post): string
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

