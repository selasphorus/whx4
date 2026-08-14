<?php

namespace atc\WHx4\Modules\Programs;

use atc\WXC\Module as BaseModule;
use atc\WHx4\Modules\Programs\Taxonomies\ProgramLabel;
use atc\WHx4\Modules\Programs\Shortcodes\ProgramItemsShortcode;
use atc\WXC\Shortcodes\ShortcodeManager;

/**
 * Program module.
 *
 * Provides ordered program/service-order item sequences
 * (repertoire, readings, sermons, etc.), independent of Personnel.
 * Owns no post types itself.
 */
final class ProgramsModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();

        parent::boot();

        // Contribute the taxonomy handler to TaxonomyRegistrar, matching
        // the AdminTag/AdminModule pattern.
        add_filter('wxc_register_taxonomy_handlers', function (array $handlers): array {
            $handlers[] = ProgramLabel::class;
            return $handlers;
        });

        ShortcodeManager::add(ProgramItemsShortcode::class);
    }

    /**
     * Program attaches to other modules' post types rather than
     * owning any itself.
     *
     * @return string[]
     */
    public function getPostTypeHandlerClasses(): array
    {
        return [];
    }
}