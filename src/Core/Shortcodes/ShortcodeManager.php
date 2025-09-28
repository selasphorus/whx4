<?php
namespace atc\WHx4\Core\Shortcodes;

use atc\WHx4\Core\Contracts\ShortcodeInterface;

final class ShortcodeManager
{
    public static function boot(): void
    {
        error_log('=== ShortcodeManager::boot() ===');

        if (did_action('init')) {
            self::registerAll();
            return;
        }

        // Late priority to give modules time to register their filters.
        add_action('init', [self::class, 'registerAll'], 99);
    }

    public static function registerAll(): void
    {
        error_log('=== ShortcodeManager::registerAll() ===');

        $classes = array_values(array_filter(
            (array) apply_filters('whx4_register_shortcodes', []),
            static fn ($fqcn) => is_string($fqcn)
                && class_exists($fqcn)
                && is_subclass_of($fqcn, ShortcodeInterface::class)
        ));

        foreach ($classes as $fqcn) {
            error_log('fqcn: ' . $fqcn);
            $instance = new $fqcn();
            add_shortcode($fqcn::tag(), [$instance, 'handle']);
        }
    }
}
