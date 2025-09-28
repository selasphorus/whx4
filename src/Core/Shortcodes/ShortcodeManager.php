<?php
namespace atc\WHx4\Core\Shortcodes;

use atc\WHx4\Core\Contracts\ShortcodeInterface;

final class ShortcodeManager
{
    /** @var array<int, class-string<Shortcode>> */
    private array $classes;

    private function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    public static function boot(): void
    {
        error_log( '=== ShortcodeManager::boot() ===' );
        // Modules/add-ons add their shortcode classes via this filter.
        $classes = array_values(array_filter(
            (array)apply_filters('whx4_register_shortcodes', []),
            static fn($fqcn) => is_string($fqcn) && class_exists($fqcn) && is_subclass_of($fqcn, Shortcode::class)
        ));

        $mgr = new self($classes);

        add_action('init', static function() use ($mgr): void {
            $mgr->registerAll();
        });
    }

    private function registerAll(): void
    {
        foreach ($this->classes as $fqcn) {
            /** @var Shortcode $instance */
            $instance = new $fqcn();
            add_shortcode($fqcn::tag(), [$instance, 'handle']);
        }
    }
}
