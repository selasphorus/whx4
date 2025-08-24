<?php

namespace atc\WHx4\Core\Contracts;

interface PluginContext
{
    public function modulesBooted(): bool;
    public function getActiveModules(): array;
    public function getActivePostTypes(): array;
}
