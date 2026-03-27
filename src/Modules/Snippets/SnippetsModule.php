<?php

namespace atc\WHx4\Modules\Snippets;

use atc\WXC\Module as BaseModule;
use atc\WXC\Shortcodes\ShortcodeManager;

use atc\WHx4\Modules\Snippets\PostTypes\Snippet;

final class SnippetsModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();

        //ShortcodeManager::add(Shortcodes\MediaPlayerShortcode::class);
        //ShortcodeManager::add(Shortcodes\AccountsShortcode::class);
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Snippet::class,
        ];
    }
}
