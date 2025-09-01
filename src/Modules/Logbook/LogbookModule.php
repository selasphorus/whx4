<?php

namespace atc\WHx4\Modules\Logbook;

use atc\WHx4\Core\Module as BaseModule;

// Post Types
use atc\WHx4\Modules\Logbook\PostTypes\LogEntry;
//use atc\WHx4\Modules\Logbook\PostTypes\YYY;
//use atc\WHx4\Modules\Logbook\PostTypes\ZZZ;

final class LogbookModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();

        add_filter( 'whx4_register_subtypes', function( array $providers ): array {
            // TODO: add use statement above to simplify these lines?
            $providers[] = new \atc\WHx4\Modules\Logbook\Subtypes\LogEntriesSubtype(); // Subtype of XXX PostType
            return $providers;
        } );
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            LogEntry::class,
            //YYY::class,
            //ZZZ::class,
        ];
    }
}
