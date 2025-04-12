<?php

namespace atc\WHx4\Modules\Supernatural;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Supernatural\PostTypes\Monster;
use atc\WHx4\Modules\Supernatural\PostTypes\Enchanter;
use atc\WHx4\Modules\Supernatural\PostTypes\Spell;

class Module extends BaseModule
{
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
    
	/*
	public function getPostTypeHandlers(): array
    {
    	$settings = get_option( 'whx4_plugin_settings', [] );
    	$moduleConfig = $settings['modules']['Supernatural']['enabledPostTypes'] ?? [];
       	//$config = $settings['postTypeConfigs']['Supernatural']['Monster'] ?? [];

        $handlers = [];

        if ( !isset( $moduleConfig['monster'] ) || $moduleConfig['monster'] !== false ) {
            $handlers[] = new Monster( $moduleConfig['monster'] ?? [] );
        }

        if ( !isset( $moduleConfig['enchanter'] ) || $moduleConfig['enchanter'] !== false ) {
            $handlers[] = new Enchanter( $moduleConfig['enchanter'] ?? [] );
        }

        if ( !isset( $moduleConfig['spell'] ) || $moduleConfig['spell'] !== false ) {
            $handlers[] = new Spell( $moduleConfig['spell'] ?? [] );
        }

        return $handlers;
        
    }
    */
}
