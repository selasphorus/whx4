<?php

namespace atc\WHx4\Modules\Admin\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class DataTable extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'data_table',
            'plural_slug'  => 'data_tables',
            'object_types' => ['admin_note', 'note'], // array of post types (by slug) to which this taxonomy applies
            'hierarchical' => true,
        ], $term);
    }
}
