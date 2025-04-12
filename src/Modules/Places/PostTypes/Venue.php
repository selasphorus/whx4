<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Core\Traits\HasSlugAndLabels;

class Venue extends PostTypeHandler
{
	use HasSlugAndLabels;
	
	protected array $config;

    public function __construct( array $config = [] )
    {
        $this->config = $config;
    }
    
    public function getSlug(): string
    {
        return $this->config['slug'] ?? 'venue';
    }
    
    public function getLabels(): array
    {
        $custom_labels = [
            'name' => 'Venues',
            'not_found' => 'Nowhere to go',
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
        return $this->config['taxonomies'] ?? [ 'venue_category', 'admin_tag' ];
    }

}

?>