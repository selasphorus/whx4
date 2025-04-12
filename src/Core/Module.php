<?php

namespace atc\WHx4\Core;

abstract class Module implements ModuleInterface
{
    abstract public function getName(): string;

    abstract public function getPostTypeHandlers(): array;

    public function boot(): void // was static
    {
        // Optional: hookable logic
    }
}
