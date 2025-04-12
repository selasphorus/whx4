<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Contracts\ModuleInterface;

abstract class Module implements ModuleInterface
{
    abstract public function getName(): string;

    abstract public function getPostTypeHandlers(): array;

    public function boot(): void // was static
    {
        // Optional: hookable logic
    }
}
