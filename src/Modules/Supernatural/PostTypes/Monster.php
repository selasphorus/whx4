<?php

namespace atc\WHx4\Modules\Supernatural\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Traits\HasSlugAndLabels;

class Monster extends PostTypeHandler 
{
	use HasSlugAndLabels;
	
	protected array $config;

    public function __construct( array $config = [] )
    {
        $this->config = $config;
    }
    
    public function getSlug(): string
    {
        return $this->config['slug'] ?? 'monster';
    }
    
    public function getLabels(): array
    {
        $custom_labels = [
            'add_new_item' => 'Summon New Monster',
            'not_found' => 'No monsters lurking nearby',
        ];
        
        // Combine custom labels with those from config, if any
    	$overrides = array_merge($custom_labels, $this->config['labels'] ?? []);
    	
    	// Merge with trait defaults, giving priority to custom overrides
    	$labels = array_merge($this->getDefaultLabels(), $overrides);
    	
    	// Use the class' slug for filtering
    	$slug = $this->getSlug();
    	
    	return apply_filters("whx4_labels_{$slug}", $labels);
    	
    }    

    public function getSupports(): array
    {
        return $this->config['supports'] ?? ['title', 'editor'];
    }
    
	/*
	public const SLUG = 'monster';
    public const LABELS = [
        'name'          => 'Monsters',
        'singular_name' => 'Monster',
        'add_new_item'  => 'Summon Monster',
        // etc...
    ];
    */
    
	public function get_cpt_content() {
		return "hello";
	}
    
    public function get_color() {
        // Assuming the color is stored as a custom field, for example, _monster_color
        return get_post_meta($this->post->ID, '_monster_color', true);
    }

    // Other methods related to the monster...
}

