<?php

namespace atc\WHx4\Modules\Personnel;

use atc\WXC\Module as BaseModule;
use atc\WHx4\Modules\Personnel\Shortcodes\PersonnelShortcode;
use atc\WXC\Shortcodes\ShortcodeManager;

/**
 * Personnel module.
 *
 * Provides a reusable "who's involved, in what role" attachment
 * mechanism for other post types (person/identity/group + role),
 * independent of the Program module. Owns no post types itself.
 */
final class PersonnelModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();

        parent::boot();

        ShortcodeManager::add(PersonnelShortcode::class);
    }

    /**
     * Personnel attaches to other modules' post types rather than
     * owning any itself.
     *
     * @return string[]
     */
    public function getPostTypeHandlerClasses(): array
    {
        return [];
    }
}