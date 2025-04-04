<?php

namespace atc\WHx4;

class Monster {
    
    private $post;

    public function __construct(WP_Post $post) {
        $this->post = $post;
    }

	public function say_hi() {
		return "Monster says hi!";
	}
	
	public function get_cpt_special_content() { //  $post_id = null 
		return $this->say_hi();
	}
    
    public function get_color() {
        // Assuming the color is stored as a custom field, for example, _monster_color
        return get_post_meta($this->post->ID, '_monster_color', true);
    }

    // Other methods related to the monster...
}

?>