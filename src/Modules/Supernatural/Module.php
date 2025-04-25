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
    	$this->registerDefaultViewRoot();

    	parent::boot();

    	//ViewLoader::registerModuleViewRoot( 'supernatural', __DIR__ . '/views' ); // default
    	// Override with custom path
    	//ViewLoader::registerModuleViewRoot( 'supernatural', WP_CONTENT_DIR . '/shared-supernatural-views' );
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

}
