<?php

namespace atc\WHx4\Modules\Media;

use atc\WXC\Module as BaseModule;
use atc\WXC\Shortcodes\ShortcodeManager;

//use atc\WHx4\Modules\People\PostTypes\Person;
//use atc\WHx4\Modules\People\PostTypes\GroupEntity;

final class MediaModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();

        ShortcodeManager::add(Shortcodes\MediaPlayerShortcode::class);
        //ShortcodeManager::add(Shortcodes\AccountsShortcode::class);
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            //Person::class,
            //GroupEntity::class,
        ];
    }
}
