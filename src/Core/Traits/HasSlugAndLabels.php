<?php

namespace atc\WHx4\Core\Traits;

// TODO: adapt as needed to apply to taxonomies and blocks as well as post types -- ???
trait HasSlugAndLabels
{
    /*protected array $config = [];

    public function setConfig( array $config ): void
    {
        $this->config = $config;
    }
    */

    public function getSlug(): string
    {
        return $this->config['slug'] ?? 'default_slug';
    }
    
    public function getLabels(): array
	{
		$slug = $this->getSlug();
	
		$defaults = $this->getDefaultLabels();
		$overrides = $this->config['labels'] ?? [];
	
		$labels = array_merge($defaults, $overrides);
	
		//return apply_filters("whx4_labels_{$slug}", $labels);
		return apply_filters("whx4_labels_{$slug}", $labels, $slug, $this);

	}
	
	public function getDefaultLabels(): array
	{
		$slug = $this->getSlug();
		$name = ucfirst($slug);
		$plural = $name . 's';
	
		return [
			'name'               => $plural,
			'singular_name'      => $name,
			'add_new_item'       => "Add New $name",
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
			// Add more defaults as needed
		];
	}
    
}
