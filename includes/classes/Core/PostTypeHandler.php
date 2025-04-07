<?php

namespace atc\WHx4\Core;

class PostTypeHandler {

	// Property to store the post object
    protected $post; //private $post;

    // Constructor to set the post object
    public function __construct($post) {
        // Store the post object in a class property
        $this->post = $post;
    }

    // Method to get the post ID
    public function get_post_id() {
        return $this->post->ID;
    }
    
    // Method to get the post title
    public function get_post_title() {
        return get_the_title($this->post->ID);
    }
	
	public function get_cpt_content() {
		
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

?>