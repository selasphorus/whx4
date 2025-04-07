<?php

namespace atc\WHx4;

class WHx4_Monster extends Core\PostTypeHandler {
	
	public function get_cpt_content() {
		return "hello";
	}
    
    public function get_color() {
        // Assuming the color is stored as a custom field, for example, _monster_color
        return get_post_meta($this->post->ID, '_monster_color', true);
    }

    // Other methods related to the monster...
}

?>