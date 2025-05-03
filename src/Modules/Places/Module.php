<?php

namespace atc\WHx4\Modules\Places;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Places\PostTypes\Venue;
//use atc\WHx4\Modules\Places\PostTypes\Address;
//use atc\WHx4\Modules\Places\PostTypes\Building;

final class PlacesModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Venue::class,
            //Address::class,
            //Building::class,
        ];
    }
}
