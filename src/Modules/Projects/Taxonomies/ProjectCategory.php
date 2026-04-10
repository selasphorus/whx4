<?php

namespace atc\WHx4\Modules\Projects\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class ProjectCategory extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'project_category',
            'plural_slug'  => 'project_categories',
            'object_types' => ['project'],
            'hierarchical' => true,
        ];
    }
}
