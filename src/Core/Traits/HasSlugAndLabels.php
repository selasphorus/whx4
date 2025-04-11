<?php

namespace atc\WHx4\Core\Traits;

// TODO: adapt as needed to apply to taxonomies and blocks as well as post types -- ???
trait HasSlugAndLabels
{
    protected array $config = [];

    public function setConfig( array $config ): void
    {
        $this->config = $config;
    }

    public function getSlug(): string
    {
        return $this->config['slug'] ?? 'default_slug';
    }
    
    /**
     * Returns default labels — can be overridden by child classes.
     */
    public function getLabels(): array // should be protected?
    {
        $slug = $this->getSlug();
        $name = ucfirst($slug);
        $plural = $name.'s';
        
        $defaultLabels = [
			'name'          => $plural,
			'singular_name' => $name,
			'add_new_item'  => 'Add New $name',
            'edit_item'          => "Edit $name",
            'new_item'           => "New $name",
            'view_item'          => "View $name",
            'search_items'       => "Search $plural",
            'not_found'          => "No $plural found",
            'not_found_in_trash' => "No $plural found in Trash",
            /*
            'menu_name'          => ucfirst($this->postTypeSlug) . 's',
            'name_admin_bar'     => ucfirst($this->postTypeSlug),
            'add_new'            => 'Add New',
            'all_items'          => 'All ' . ucfirst($this->postTypeSlug) . 's',
            'parent_item_colon'  => 'Parent ' . ucfirst($this->postTypeSlug) . 's:',
            */
		];
	
		$labels = array_merge( $defaultLabels, $this->config['labels'] ?? [] );
	
		return apply_filters( "whx4_labels_{$slug}", $labels );
		/*
		// Example of how to hook into filter established above:
		add_filter('whx4_labels_monster', function($labels, $slug, $class) {
			if ($slug === 'monster') {
				$labels['add_new_item'] = 'Summon Monster';
			}
			return $labels;
		}, 10, 3);
		*/
    
    }
    
}
