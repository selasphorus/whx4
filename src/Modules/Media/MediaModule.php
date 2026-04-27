<?php

namespace atc\WHx4\Modules\Media;

use atc\WXC\Module as BaseModule;
use atc\WXC\Shortcodes\ShortcodeManager;
use atc\WHx4\Modules\Media\Utils\MediaDisplay;

final class MediaModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();

        ShortcodeManager::add(Shortcodes\MediaPlayerShortcode::class);
        
		add_filter('wxc_post_image', function(string $image, \WP_Post $post, string $size, array $atts): string {
			if ($image !== '') {
				return $image;
			}
			$atts['post_id']      = $post->ID;
			$atts['img_size']     = $size;
			$atts['format']       = $atts['format'] ?? 'excerpt';
			$atts['echo']         = false;
			$atts['return_value'] = 'html';
			return (string) (MediaDisplay::renderPostImage($atts) ?? '');
		}, 10, 4);
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
        ];
    }
}
