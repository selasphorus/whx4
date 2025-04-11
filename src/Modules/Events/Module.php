<?php

namespace atc\WHx4\Modules\Events;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\People\PostTypes\Event;
use atc\WHx4\Modules\People\PostTypes\RecurringEvent;
use atc\WHx4\Modules\People\PostTypes\EventSeries;

class Module extends BaseModule
{
    public static function getName(): string
    {
        return 'Events';
    }

    public static function getPostTypeHandlers(): array
    {
        return [
            Event::class,
            RecurringEvent::class,
            EventSeries::class,
        ];
    }
}