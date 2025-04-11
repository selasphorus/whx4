<?php

namespace atc\WHx4\Modules\People;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\People\PostTypes\Person;
use atc\WHx4\Modules\People\PostTypes\GroupEntity;
//use atc\WHx4\Modules\People\PostTypes\Identity;

class Module extends BaseModule
{
    public static function getName(): string
    {
        return 'People';
    }

    public static function getPostTypeHandlers(): array
    {
        return [
            Person::class,
            GroupEntity::class,
            //Identity::class,
        ];
    }
}