<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Contracts\ModuleInterface;

abstract class Module implements ModuleInterface
{
    abstract public function getName(): string;

    abstract public function getPostTypeHandlers(): array;
	
	public function getPostTypes(): array
	{
		error_log( '=== atc\WHx4\Core\Module -- getPostTypes() ===' );
		$postTypes = [];
	
		foreach( $this->getPostTypeHandlers() as $class ) {
			try {
				$handler = new $class();
				$slug = $handler->getSlug();
				$label = $handler->getLabels()['singular_name']; // or getLabel()					
				$postTypes[ $slug ] = $label;
			} catch( \Throwable $e ) {
				error_log( "Error in post type handler {$class}: " . $e->getMessage() );
			}
		}
		
		error_log("postTypes: " . print_r($postTypes, true));
		
		return $postTypes;
	}


    public function boot(): void // was static
    {
        // Optional: hookable logic
    }
}
