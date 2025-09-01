<?php

namespace atc\WHx4\Modules\[ModuleName]\Fields;

use atc\WHx4\Core\Contracts\FieldGroupInterface;
use atc\WHx4\Core\Contracts\SubtypeFieldGroupInterface;
use atc\WHx4\Core\SubtypeRegistrar;

final class XXXSubtypePTFields implements FieldGroupInterface, SubtypeFieldGroupInterface
{
    public function getPostType(): string
    {
        return 'xxx_post_type_slug';
    }

    public function getSubtypeSlug(): string
    {
        return 'xxx_subtype_slug';
    }

    public static function register(): void
    {
        error_log( '=== XXXSubtypePTFields: register()) ===' );
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        //$tax = SubtypeRegistrar::getTaxonomyForPostType( 'xxx_post_type_slug' ); // rex_xxx_type
        $taxonomy = "xxx_parent_taxonomy";

        //use atc\WHx4\Migrations\FieldKeyMigrator;
        /*
        // Migrate
        FieldKeyMigrator::migrate([
            'field_624775f4b6221' => [
                'new_field_key' => 'field_whx4_modulename_first_name',
                'old_meta_key'  => 'first_name',
                'new_meta_key'  => 'whx4_modulename_first_name',
            ],
            'field_abc123xyz456' => [
                'new_field_key' => 'field_whx4_notes',
                // no meta_key rename
            ],
        ]);
        */

        acf_add_local_field_group( array() );
    }
}
