<?php

namespace atc\WHx4\Modules\Admin\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class AdminNoteCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'admin_tag',
            'plural_slug'  => 'admin_tags',
            'object_types' => ['adminnote_category', 'note'], // array of post types (by slug) to which this taxonomy applies
            'hierarchical' => true,
        ], $term);
    }
}
