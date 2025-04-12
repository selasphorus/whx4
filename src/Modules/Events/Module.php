<?php

namespace atc\WHx4\Modules\Events;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Events\PostTypes\Event;
//use atc\WHx4\Modules\Events\PostTypes\RecurringEvent;
//use atc\WHx4\Modules\Events\PostTypes\EventSeries;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'Events';
    }

    public function getPostTypeHandlers(): array
    {
        return [
            Event::class,
            //RecurringEvent::class,
            //EventSeries::class,
        ];
    }
}