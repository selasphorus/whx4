<?php

namespace atc\WHx4\Modules\Supernatural;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Core\Query\PostQuery;
use atc\WHx4\Core\Shortcodes\ShortcodeManager;

use atc\WHx4\Modules\Supernatural\PostTypes\Monster;
use atc\WHx4\Modules\Supernatural\PostTypes\Enchanter;
use atc\WHx4\Modules\Supernatural\PostTypes\Spell;

final class SupernaturalModule extends BaseModule
{
    public function boot(): void
    {
        error_log( '=== SupernaturalModule::boot() ===' );
        $this->registerDefaultViewRoot();

        parent::boot();

        //ViewLoader::registerModuleViewRoot( 'supernatural', __DIR__ . '/views' ); // default
        // Override with custom path
        //ViewLoader::registerModuleViewRoot( 'supernatural', WP_CONTENT_DIR . '/shared-supernatural-views' );
        
        // Register shortcodes
        ShortcodeManager::add(\atc\WHx4\Modules\Supernatural\Shortcodes\SupernaturalShortcode::class);
    }

    /*
    // Optional: override defaults that match to namespace
    public function getSlug(): string
    {
        return 'spooky';
    }
    public function getName(): string
    {
        return 'Spooky Things';
    }
    */

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Monster::class,
            //Enchanter::class,
            //Spell::class,
        ];
    }
    
    public function getModuleStats(): array
    {
        return [
            'monsters'   => wp_count_posts('monster')->publish ?? 0,
            //'enchanters' => wp_count_posts('enchanter')->publish ?? 0,
        ];
    }
    
    /**
     * @return \WP_Post[]  All Monster posts matching the color.
     * This is a sample function to show a module-level find method by meta_key
     */
    public function findMonstersByColor(string $color, array $options = []): array
    {
        $defaults = [
            'per_page'  => -1,        // all
            'orderby'   => 'title',
            'order'     => 'ASC',
        ];

        $params = array_replace($defaults, $options, [
            'post_type' => 'monster',
            // Generic MetaQueryBuilder spec (equals)
            'meta'      => [
                ['key' => 'monster_color', 'equals' => $color],
            ],
        ]);

        return PostQuery::find($params);
    }
}
