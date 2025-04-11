<?php

namespace atc\WHx4;

class Building extends Core\PostTypeHandler {
	
	public const SLUG = 'building';
    
	public function get_cpt_content() {
		return "hello";
	}
}

?>