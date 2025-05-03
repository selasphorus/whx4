<?php

namespace atc\WHx4\Core\Traits;

// TODO: adapt as needed to apply to taxonomies and blocks as well as post types -- ???
trait HasTypeProperties
{
	abstract public function getConfig(): array;
    abstract public function getType(): string; // 'post_type' or 'taxonomy'

    public function getSlug(): string
    {
        error_log( 'HasTypeProperties::getSlug() config: ' . print_r( $this->getConfig(), true ) );
        return $this->getConfig()['slug'] ?? strtolower( basename( str_replace( '\\', '/', static::class ) ) );
    }

    public function getPluralSlug(): string
    {
        return $this->getConfig()['plural_slug'] ?? $this->getSlug() . 's';
    }

    public function getLabels(): array
	{
		//error_log('=== getLabels() ===');
		$slug = $this->getSlug();
		$defaults = $this->getDefaultLabels();
		$overrides = $this->config['labels'] ?? [];
		// Merge defaults with overrides
		$labels = array_merge($defaults, $overrides);
    	//error_log( 'labels (merged): ' . print_r( $labels, true ) );
    	// Filter the array
		$filtered = apply_filters("whx4_labels_{$slug}", $labels, $slug, $this);
		return apply_filters("whx4_labels", $filtered, $slug, $this);
	}

	public function getDefaultLabels(): array
	{
		$singular = ucfirst( $this->getSlug() );
        $plural   = ucfirst( $this->getPluralSlug() );

		return [
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new_item'       => "Add New $singular",
            'edit_item'          => "Edit $singular",
            'new_item'           => "New $singular",
            'view_item'          => "View $singular",
            'view_items'         => "View $plural",
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

    public function getCapabilities(): array
    {
        $custom = $this->getConfig()['capabilities'] ?? [];
        return array_merge( $this->getDefaultCapabilities(), $custom );
    }

    public function getDefaultCapabilities(): array
    {
        $type     = $this->getType();
        $singular = $this->getSlug();
        $plural   = $this->getPluralSlug() ?? "{$singular}s";

        if ( $type === 'taxonomy' ) {
            return [
                'manage_terms' => "manage_{$plural}",
                'edit_terms'   => "edit_{$plural}",
                'delete_terms' => "delete_{$plural}",
                'assign_terms' => "assign_{$plural}",
            ];
        }

        return [
            'edit_post'          => "edit_{$singular}",
            'read_post'          => "read_{$singular}",
            'delete_post'        => "delete_{$singular}",
            'edit_posts'         => "edit_{$plural}",
            'edit_others_posts'  => "edit_others_{$plural}",
            'publish_posts'      => "publish_{$plural}",
            'read_private_posts' => "read_private_{$plural}",
        ];
    }

	public function isHierarchical(): bool
	{
		$default = $this->getType() === 'taxonomy';
		return $this->getConfig()['hierarchical'] ?? $default;
	}

}

