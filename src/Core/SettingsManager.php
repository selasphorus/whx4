<?php

namespace atc\WHx4\Core;

use atc\WHx4\Plugin;

final class SettingsManager
{
    private const OPTION = 'whx4_plugin_settings';

    public function getOption(): array
    {
        $saved = get_option(self::OPTION, []);
        //$saved = get_option( 'whx4_plugin_settings', [] );
        //
        $defaults = [
            'active_modules'     => [],
            'enabled_post_types' => [],
            'enabled_taxonomies' => [],
        ];

        //return array_merge( $defaults, $saved );
        return array_merge($defaults, is_array($saved) ? $saved : []);
    }

    public function save(array $patch): void
    {
        $current = $this->getOption();
        update_option(self::OPTION, array_replace_recursive($current, $patch));
    }

    /**
     * Seed first-run defaults once: activate modules by default.
     * Call this AFTER all modules have been registered via filters.
     *
     * @param array<string,class-string> $availableModules keyed e.g. by slug => FQCN
     */
    public function ensureInitialized(array $availableModules): void
    {
        $opt = $this->getOption();

        $needsSeeding =
            empty($opt['active_modules']) &&
            get_option('whx4_initialized', 0) !== 1;

        if (!$needsSeeding) {
            return;
        }

        $defaultActive = $this->computeDefaultActiveModules(array_keys($availableModules));

        $this->save([
            'active_modules' => $defaultActive,
            // leave enabled_post_types empty so it falls back to “all”
        ]);

        update_option('whx4_initialized', 1);
    }

    /**
     * Filterable default: “all discovered modules” unless overridden.
     *
     * @param string[] $allModuleSlugs
     * @return string[]
     */
    public function computeDefaultActiveModules(array $allModuleSlugs): array
    {
        /** @var string[] */
        $defaults = apply_filters('whx4_default_active_modules', $allModuleSlugs);
        return array_values(array_unique(array_filter($defaults, 'is_string')));
    }

    /** @return string[] */
    public function getActiveModuleSlugs(): array
    {
        return $this->getOption()['active_modules'];
    }

    /** @return array<string,string[]> moduleSlug => [postTypeSlug,...] */
    public function getEnabledPostTypeSlugsByModule(): array
    {
        return $this->getOption()['enabled_post_types'];
    }

    /** @return string[] flat list */
    public function getEnabledPostTypeSlugs(): array
    {
        $grouped = $this->getEnabledPostTypeSlugsByModule();
        //$grouped = $this->getOption()['enabled_post_types'] ?? [];
        $flat = [];
        foreach ( $grouped as $moduleSlugs ) {
            if ( is_array($moduleSlugs) ) {
                $flat = array_merge($flat, $moduleSlugs);
            }
        }
        //return array_unique( $flat );
        return array_values(array_unique($flat));
    }

    //public function getEnabledTaxonomySlugs(): array { ... }

}
