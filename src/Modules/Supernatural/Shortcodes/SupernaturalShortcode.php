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
        $monsters = $module->findMonstersByColor('blue') ?? [];
        //$result = Event::find($atts);
        $monsterPosts  = $monsters['posts'] ?? [];

        // Pagination info for the view.
        $pagination = $monsters['pagination'] ?? ['found' => 0, 'max_pages' => 0, 'paged' => 1];

        // Troubleshooting info
        $info .= "[" . $monsters['pagination']['found'] . "] posts found<br />";
        //$info .= "posts: <pre>" . print_r($posts, true) . "</pre>";
        //$info .= "atts: <pre>" . print_r($atts, true) . "</pre>";
        //$info .= "wp_args: <pre>" . print_r($result['debug']['args'], true) . "</pre>";
        //$info .= "query_request: <pre>" . $result['debug']['query_request'] . "</pre>";

        // Handler factory so views can call CPT methods safely.
        $handlerFactory = [PostTypeHandler::class, 'getHandlerForPost'];


        // Choose a view variant (list|grid|table); fall back to list.
        //$viewVariant = in_array($atts['view'], ['list', 'grid', 'table'], true) ? $atts['view'] : 'list';
        //$view = $viewVariant;
        
        $view = "module-view-test";
        
        $vars = [
            'posts'      => $monsterPosts,
            'handler'    => $handlerFactory,
            //'atts'       => $atts,
            //'pagination' => $pagination,
            'stats' => $stats,
            //'blueMonsterPosts' => $blueMonsterPosts,
            //'info' => $info, // for TS -- deprecate in favor of:
            // Optionally pass debug through when WHX4_DEBUG is on:
            'debug'      => $monsters['debug'] ?? null,
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
