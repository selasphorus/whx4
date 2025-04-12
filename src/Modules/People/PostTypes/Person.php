<?php

namespace atc\WHx4\Modules\People\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Core\Traits\HasSlugAndLabels;

class Person extends PostTypeHandler
{
	use HasSlugAndLabels;
	
	protected array $config;

    public function __construct( array $config = [] )
    {
        $this->config = $config;
    }
    
    public function getSlug(): string
    {
        return $this->config['slug'] ?? 'person';
    }
    
    public function getLabels(): array
    {
        $custom_labels = [
            'name' => 'People',
            'add_new_item' => 'Procreate',
            'not_found' => 'No people loitering nearby',
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

    public function getTaxonomies(): array
    {
        return $this->config['taxonomies'] ?? [ 'person_category', 'person_title', 'admin_tag' ];
    }
    
}

