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
        
        add_filter('wxc_post_image', function(string $image, \WP_Post $post, string $size, array $atts): string {
			if ($image !== '') {
				return $image;
			}
			return MediaDisplay::getPostImage($post, $size, $atts) ?? '';
		}, 10, 4);
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            //Person::class,
            //GroupEntity::class,
        ];
    }
}
