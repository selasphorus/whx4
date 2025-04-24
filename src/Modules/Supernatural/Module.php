<?php

namespace atc\WHx4\Modules\Supernatural;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Supernatural\PostTypes\Monster;
use atc\WHx4\Modules\Supernatural\PostTypes\Enchanter;
use atc\WHx4\Modules\Supernatural\PostTypes\Spell;

class Module extends BaseModule
{
    public function boot(): void
    {
    	ViewLoader::registerModuleViewRoot( 'supernatural', __DIR__ . '/views' );

        $this->applyTitleDefaults( 'monster', [
            'line_breaks'   => true,
            'show_subtitle' => true,
            'hlevel_sub'    => 2,
            'called_by'     => 'supernatural-module',
        ]);

        $this->applyTitleDefaults( 'spell', [
            'line_breaks' => false,
        ]);
    }

    public function getName(): string // was static
    {
        return 'Supernatural';
    }

	public function getPostTypeHandlers(): array
    {
        return [
            Monster::class,
            //Enchanter::class,
            //Spell::class,
        ];
    }

}
