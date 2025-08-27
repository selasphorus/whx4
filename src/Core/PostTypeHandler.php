<?php

namespace atc\WHx4\Core;

use WP_Post;
use atc\WHx4\Core\BaseHandler;
use atc\WHx4\Core\Traits\AppliesTitleArgs;

abstract class PostTypeHandler extends BaseHandler
{
	use AppliesTitleArgs;

	// Property to store the post object
    protected $post; // better private?
    protected const TYPE = 'post_type';

    // Constructor to set the config and post object

    public function __construct( array $config = [], WP_Post|null $post = null )
    {
        parent::__construct( $config, $post );
    }

    public function boot(): void
	{
		// Optional: common setup logic for all post types can go here
	}

    public function getCapType(): array {
        $capType = $this->getConfig()['capability_type'] ?? [];
        if ( empty($capType) ) { $capType = [ $this->getSlug(), $this->getPluralSlug() ]; } else if ( !is_array($capType) ) { $capType = [$capType, "{$capType}s" ]; };
        return $capType;
        //return $this->getConfig()['capability_type'] ?? [ $this->getSlug(), $this->getPluralSlug() ];
    }

    public function getSupports(): array {
        return $this->getConfig()['supports'] ?? [ 'title', 'editor' ];
    }

    public function getTaxonomies(): array {
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

    public function getMenuIcon(): ?string {
        return $this->getConfig()['menu_icon'] ?? 'dashicons-superhero';
    }

    ////

    // Method to get the post ID
    public function getPostID()
    {
        return $this->post->ID;
    }

    // Method to get the post title
    public function get_post_title()
    {
        return get_the_title($this->getPostID());
    }

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

