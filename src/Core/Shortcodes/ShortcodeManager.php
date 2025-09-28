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

    /*public static function registerAll(): void
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
    }*/
    
    public static function registerAll(): void
	{
		error_log('=== ShortcodeManager::registerAll() ===');
	
		$candidates = (array) apply_filters('whx4_register_shortcodes', []);
		error_log('Shortcode candidates: ' . json_encode($candidates, JSON_UNESCAPED_SLASHES));
	
		$classes = [];
		foreach ($candidates as $fqcn) {
			if (!is_string($fqcn)) {
				error_log('Rejected: non-string value from whx4_register_shortcodes');
				continue;
			}
			if (!class_exists($fqcn)) {
				error_log("Rejected: class does not exist: {$fqcn}");
				continue;
			}
			if (!is_subclass_of($fqcn, \atc\WHx4\Core\Contracts\ShortcodeInterface::class)) {
				error_log("Rejected: {$fqcn} does not implement ShortcodeInterface");
				continue;
			}
			$classes[] = $fqcn;
		}
	
		foreach ($classes as $fqcn) {
			error_log('Registering shortcode class: ' . $fqcn);
			$instance = new $fqcn();
			add_shortcode($fqcn::tag(), [$instance, 'handle']);
		}
	}

}
