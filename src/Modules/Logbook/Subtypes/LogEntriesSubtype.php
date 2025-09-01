<?php

namespace atc\WHx4\Modules\Logbook\Subtypes;

use atc\WHx4\Core\Contracts\SubtypeInterface;

final class LogEntriesSubtype implements SubtypeInterface
{
    public function getPostType(): string
    {
        return 'post';
    }

    public function getSlug(): string
    {
        return 'log_entry';
    }

    public function getLabel(): string
    {
        return 'Log Entries';
    }

    public function getTermArgs(): array
    {
        return []; // e.g. ['description' => 'Organizations that employ people']
    }
}
