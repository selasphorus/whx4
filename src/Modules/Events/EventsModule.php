<?php

namespace atc\WHx4\Modules\Events;

use atc\WHx4\Core\Module as BaseModule;
use atc\WHx4\Modules\Events\PostTypes\Event;
//use atc\WHx4\Modules\Events\PostTypes\RecurringEvent;
//use atc\WHx4\Modules\Events\PostTypes\EventSeries;
use atc\WHx4\Modules\Events\Utils\EventInstances;
use atc\WHx4\Modules\Events\Utils\AjaxController;
use atc\WHx4\Core\Shortcodes\ShortcodeManager;

final class EventsModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();
        EventInstances::register();
        AjaxController::register();
        //
        error_log('EventsModule::boot -> calling ShortcodeManager::add');
        ShortcodeManager::add(\atc\WHx4\Modules\Events\Shortcodes\EventsShortcode::class);

		// Keep filter for 3P extensibility (optional)
		/*
        add_filter('whx4_register_shortcodes', static function(array $classes): array {
            $classes[] = \atc\WHx4\Modules\Events\Shortcodes\EventsShortcode::class;
            return $classes;
        });*/
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
