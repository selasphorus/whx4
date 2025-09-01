<?php

namespace atc\WHx4\Modules\Core;

use atc\WHx4\Core\Module as BaseModule;

// Post Types
use atc\WHx4\Modules\Core\PostTypes\Post;
use atc\WHx4\Modules\Core\PostTypes\Page;
use atc\WHx4\Modules\Core\PostTypes\Attachment;

final class CoreModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();

        parent::boot();
    }

    /** @return array<string, class-string> */
    public function getPostTypeHandlerClasses(): array
    {
        return [
            'post'       => Post::class,
            'page'       => Page::class,
            'attachment' => Attachment::class,
        ];
    }
}
