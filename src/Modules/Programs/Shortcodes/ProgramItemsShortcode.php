<?php

namespace atc\WHx4\Modules\Programs\Shortcodes;

use atc\WXC\Contracts\ShortcodeInterface;
use atc\WHx4\Modules\Programs\Display\ProgramItemsRenderer;

/**
 * Renders the program items sequence for the current post via shortcode.
 */
class ProgramItemsShortcode implements ShortcodeInterface
{
    public static function tag(): string
    {
        return 'whx4_program_items';
    }

    /**
     * @param array<string, mixed> $atts
     */
    public function render(array $atts, string $content = '', string $tag = ''): string
    {
        return ProgramItemsRenderer::render($atts);
    }
}