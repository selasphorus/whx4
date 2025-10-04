<?php

namespace atc\WHx4\Core\Contracts;

use atc\WHx4\Core\Contracts\ModuleInterface;

interface PluginContext
{
    public function getSettingsManager();
    public function modulesBooted(): bool;
    public function getActiveModules(): array;
    public function getActivePostTypes(): array;
    //
    public function getModule(string $key): ?ModuleInterface;
}
