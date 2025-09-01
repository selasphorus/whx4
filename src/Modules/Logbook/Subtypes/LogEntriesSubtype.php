<?php

namespace atc\WHx4\Modules\Logbook\Subtypes;

use atc\WHx4\Core\Contracts\SubtypeInterface;

final class XXXSubtype implements SubtypeInterface
{
    public function getPostType(): string
    {
        return 'xxx_post_type_slug';
    }

    public function getSlug(): string
    {
        return 'xxx_subtype_slug';
    }

    public function getLabel(): string
    {
        return 'XXX Subtype Name';
    }

    public function getTermArgs(): array
    {
        return []; // e.g. ['description' => 'Organizations that employ people']
    }
}
