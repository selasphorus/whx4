<?php

namespace atc\WHx4\Modules\Supernatural\Shortcodes;

use atc\WHx4\Core\Contracts\ShortcodeInterface;
use atc\WHx4\Core\WHx4;
use atc\WHx4\Utils\ClassInfo;
use atc\WHx4\Core\ViewLoader;

final class SupernaturalShortcode implements ShortcodeInterface
{
    public static function tag(): string
    {
        return 'whoa_supernatural';
    }

    public function render(array $atts = [], string $content = '', string $tag = ''): string
    {
        $ctx = WHx4::ctx();
        $key = ClassInfo::getModuleKey(self::class); // 'supernatural'
        $module = $ctx->getModule($key);

        if (!$module) {
            return '<p>Supernatural module inactive.</p>';
        }

        $stats = $module->getModuleStats();
        
        // Handler factory so views can call CPT methods safely.
        //$handlerFactory = [PostTypeHandler::class, 'getHandlerForPost'];

        // Choose a view variant (list|grid|table); fall back to list.
        //$viewVariant = in_array($atts['view'], ['list', 'grid', 'table'], true) ? $atts['view'] : 'list';
        //$view = $viewVariant;
        
        $view = "module-view-test";
        
        $vars = [
            //'posts'      => $posts,
            //'handler'    => $handlerFactory,
            //'atts'       => $atts,
            //'pagination' => $pagination,
            'stats' => $stats,
            'info' => $info, // for TS -- deprecate in favor of:
            // Optionally pass debug through when WHX4_DEBUG is on:
            'debug'      => $result['debug'] ?? null,
        ];

        return ViewLoader::renderToString(
            $view,
            $vars,
            ['kind' => 'partial', 'module' => 'supernatural'] //, 'post_type' => self::CPT
        );

        /*ob_start(); ?>
        <div class="whx4-supernatural">
            <p><strong>Monsters:</strong> <?php echo (int)$stats['monsters']; ?></p>
        </div>
        <?php
        return ob_get_clean();*/
    }
}
