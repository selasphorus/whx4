<?php

namespace atc\WHx4\Modules\People;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\People\PostTypes\Person;
//use atc\WHx4\Modules\People\PostTypes\GroupEntity;
//use atc\WHx4\Modules\People\PostTypes\Identity;

class Module extends BaseModule
{
    public function boot(): void
    {
    	$this->registerDefaultViewRoot();

    	parent::boot();

    	/*$this->applyTitleArgs( 'people', [
            'line_breaks'    => true,
            'show_subtitle'  => true,
            'hlevel_sub'     => 4,
        ] );*/
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Person::class,
            //GroupEntity::class,
            //Identity::class,
        ];
    }
}
