<?php

namespace atc\WHx4\Modules\Personnel\Shortcodes;

use atc\WXC\Contracts\ShortcodeInterface;
use atc\WHx4\Modules\Personnel\Display\PersonnelRenderer;

/**
 * Renders the personnel repeater for the current post via shortcode.
 */
class PersonnelShortcode implements ShortcodeInterface
{
    public static function tag(): string
    {
        return 'whx4_personnel';
    }

    /**
     * @param array<string, mixed> $atts
     */
    public function render(array $atts, string $content = '', string $tag = ''): string
    {
        return PersonnelRenderer::render($atts);
    }
}