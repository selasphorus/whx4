<?php

namespace atc\WHx4\Modules\Projects;

use atc\WHx4\Core\Module as BaseModule;

// Post Types
use atc\WHx4\Modules\Projects\PostTypes\Project;
//use atc\WHx4\Modules\Projects\PostTypes\YYY;
//use atc\WHx4\Modules\Projects\PostTypes\ZZZ;

final class ProjectsModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Project::class,
            //YYY::class,
            //ZZZ::class,
        ];
    }
}
