<?php

namespace atc\WHx4\Modules\Places;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Places\PostTypes\Venue;
use atc\WHx4\Modules\Places\PostTypes\Address;
use atc\WHx4\Modules\Places\PostTypes\Building;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'Places';
    }

    public function getPostTypeHandlers(): array
    {
        return [
            Venue::class,
            Address::class,
            Building::class,
        ];
    }
}