<?php

namespace atc\WHx4\Modules\Events;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Events\PostTypes\Event;
//use atc\WHx4\Modules\Events\PostTypes\RecurringEvent;
//use atc\WHx4\Modules\Events\PostTypes\EventSeries;

final class EventsModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Event::class,
            //RecurringEvent::class,
            //EventSeries::class,
        ];
    }
}
