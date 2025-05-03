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

    // Constructor to set the post object
    /*public function __construct($post)
    {
        // Store the post object in a class property
        $this->post = $post;
    }*/

    public function __construct( array $config = [], WP_Post|null $post = null )
    {
        parent::__construct( $config, $post );
    }

    public function boot(): void
	{
		// Optional: common setup logic for all post types can go here
	}

    public function getSupports(): array {
        return $this->getConfig()['supports'] ?? [ 'title', 'editor' ];
    }

    public function getTaxonomies(): array {
        return $this->getConfig()['taxonomies'] ?? [ 'admin_tag' ];
    }

    public function getMenuIcon(): ?string {
        return $this->getConfig()['menu_icon'] ?? null;
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

