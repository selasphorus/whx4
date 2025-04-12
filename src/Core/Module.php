<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Contracts\ModuleInterface;

abstract class Module implements ModuleInterface
{
    abstract public function getName(): string;

    abstract public function getPostTypeHandlers(): array;

	public function getPostTypes(): array
	{
		$postTypes = [];
	
		foreach( $this->getPostTypeHandlers() as $class ) {
			$handler = new $class();
			$postTypes[ $handler->getSlug() ] = $handler->getLabels()['singular_name']; // or getLabel()?
		}
	
		return $postTypes;
	}

    public function boot(): void // was static
    {
        // Optional: hookable logic
    }
}
