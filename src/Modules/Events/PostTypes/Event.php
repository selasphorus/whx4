<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Core\Traits\HasTypeProperties;

class Event extends PostTypeHandler 
{
	use HasTypeProperties;
	
	protected array $config;
	
	/*
	//public $event_id;
	public $post_id;
	public $event_parent;
	public $event_slug;
	public $event_owner;
	public $event_name;
	*/

    public function __construct( array $config = [] )
    {
        $this->config = $config;
    }
    
    public function getSlug(): string
    {
        return $this->config['slug'] ?? 'event';
    }
    
    public function getLabels(): array
    {
        $custom_labels = [
            //'add_new_item' => 'Summon New Monster',
            //'not_found' => 'No monsters lurking nearby',
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
	
	public function getCPTContent() {
		
		$post_id = $this->get_post_id();
		
	}
}
