<?php

namespace atc\WHx4\Core\Contracts;

interface ModuleInterface
{
    public function getName(): string;
    public function getPostTypeHandlerClasses(): array;
}
