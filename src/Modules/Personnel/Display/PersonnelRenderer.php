<?php

namespace atc\WHx4\Modules\Personnel\Display;

/**
 * Renders the personnel list (name/role rows) for a given post.
 */
class PersonnelRenderer
{
    /**
     * @param array<string, mixed> $atts Shortcode/render attributes (e.g. post_id override).
     */
    public static function render(array $atts = []): string
    {
        // TODO: port row-building logic from the current procedural
        // display code (row_type handling: default / header / role_only /
        // name_only), and group-vs-individual personnel resolution once
        // that open item is decided.
        return '';
    }
}