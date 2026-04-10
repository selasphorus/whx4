<?php

namespace atc\WHx4\Modules\Events\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

// TODO: consider generalizing this taxonomy to something like info_label or item_label? Or at least remove the limit to events so it can also be used with e.g. Projects?
class ProgramLabel extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'program_label',
            'plural_slug'  => 'program_labels',
            'object_types' => ['event'],
            'hierarchical' => false,
            'meta_box_cb'  => false,
        ];
    }
}
