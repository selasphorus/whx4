<?php

namespace atc\WHx4\Modules\Personnel\Fields;

use atc\WXC\Contracts\FieldGroupInterface;
use atc\WXC\Contracts\MultiPostTypeFieldGroupInterface;

/**
 * ACF field group for Personnel: attaches person/identity/group
 * entries with roles to other content.
 *
 * See docs/FieldGroupStandards.md.
 *
 * TODO: port the 'personnel' repeater sub-fields from the original
 * combined Personnel & Program group (group_whx4_pnp), preserving
 * every 'key' value verbatim. Decompose into private static builder
 * methods (e.g. buildPersonnelRepeater()) rather than one large array.
 *
 * OPEN ITEM: the original group used a shared 'show_all_fields' toggle
 * (field_whx4_606b4d8173944) referenced by conditional logic on both
 * Personnel and Program sub-fields. Now that the group is split in two,
 * decide whether each group gets its own independent toggle (new key)
 * or whether cross-group conditional references are viable on the same
 * screen. Resolve before porting the full field set.
 */
final class PersonnelFields implements FieldGroupInterface, MultiPostTypeFieldGroupInterface
{
    /**
     * Post type slugs this field group targets.
     * Starting list matches the original combined group; adjust as needed.
     *
     * @return string[]
     */
    public function getPostTypes(): array
    {
        return ['whx4_event', 'event', 'event-recurring', 'project'];
    }

    /**
     * Register the field group with ACF.
     */
    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(static::defineConfig());
    }

    /**
     * Full ACF field group config.
     *
     * @return array<string, mixed>
     */
    private static function defineConfig(): array
    {
        return [
            'key' => 'group_whx4_personnel', // TODO: confirm final key before first save to any site
            'title' => 'WHx4: Personnel',
            'fields' => [
                // TODO: buildPersonnelHeaderField(), buildPersonnelRepeater(), etc.
            ],
            'location' => [], // TODO: build from getPostTypes()
            'menu_order' => 0,
            'position' => 'acf_after_title',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'show_in_rest' => 0,
        ];
    }
}