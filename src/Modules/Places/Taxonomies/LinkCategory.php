<?php

namespace atc\WHx4\Modules\Places\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

// TODO: figure out why this taxonomy isn't showing up (WIP 09/01/25)
class LinkCategory extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'link_category',
            'plural_slug'  => 'link_categories',
            'object_types' => ['link'],
            'hierarchical' => true,
        ];
    }
}
