<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\BaseHandler;

abstract class TaxonomyHandler extends BaseHandler
{
    public function getObjectTypes(): array {
        return $this->getConfig()['object_types'] ?? [];
    }

}
