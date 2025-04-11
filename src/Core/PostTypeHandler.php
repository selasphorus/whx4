<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Traits\HasSlugAndLabels;

abstract class PostTypeHandler
{
    use HasSlugAndLabels;
    
	// Property to store the post object
    protected $post; // better private?

    // Constructor to set the post object
    public function __construct($post) {
        // Store the post object in a class property
        $this->post = $post;
    }
    
    public function getSupports(): array
    {
        return $this->config['supports'] ?? ['title', 'editor'];
    }

    public function getTaxonomies(): array
    {
        return $this->config['taxonomies'] ?? [ 'admin_tag' ];
    }

    public function getCapabilities(): ?array //public static function getCapabilities(): ?array
    {
        //return $this->config['capabilities'] ?? ['edit_posts', 'delete_posts'];
        //return ['edit_posts', 'delete_posts']; // default example
        return null; // override if needed
    }
    
    public function getArgs(): array //public static function getArgs(): array {
    {
        return [
            'public'       => true,
            'show_in_rest' => true,
            'labels'       => $this->getLabels(), //'labels'       => static::getLabels(),
            'supports'		=> $this->getSupports(), //'supports'     => ['title', 'editor'],
        ];
    }
    
    // Method to get the post ID
    public function get_post_id()
    {
        return $this->post->ID;
    }
    
    // Method to get the post title
    public function get_post_title()
    {
        return get_the_title($this->post->ID);
    }
	
	public function get_cpt_content()
	{
		$post_id = $this->get_post_id();
		
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

