<?php

namespace atc\WHx4\Modules\Program\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

/**
 * Taxonomy for labeling program items (e.g. "Prelude", "Offertory").
 *
 * Moved from Modules\Events\Taxonomies — Program is a standalone module,
 * not owned by Events, so the taxonomy follows the module that owns
 * the Program field group and item logic.
 */
 
// TODO: consider generalizing this taxonomy to something like info_label or item_label? Or at least remove the limit to events so it can also be used with e.g. Projects?
class ProgramLabel extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'program_label',
            'plural_slug'  => 'program_labels',
            'object_types' => ['event'],
            // TODO: consider '*' wildcard (per AdminTag pattern) if Program
            // is expected to attach to most/all future CPTs rather than a
            // fixed list.
            'hierarchical' => false,
            'meta_box_cb'  => false,
        ];
    }
}
