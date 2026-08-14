<?php

namespace atc\WHx4\Modules\Programs\Display;

/**
 * Renders the program items sequence (row_type: default / header /
 * program_note / label_only / title_only) for a given post.
 */
class ProgramItemsRenderer
{
    /**
     * @param array<string, mixed> $atts Shortcode/render attributes (e.g. post_id override).
     */
    public static function render(array $atts = []): string
    {
        // TODO: port row-building logic from the current procedural
        // display code, including authorship display via the shared
        // AuthorshipResolver utility (location TBD — WHx4-level shared
        // utility, not owned by Program).
        return '';
    }
}