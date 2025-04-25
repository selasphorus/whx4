<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Contracts\ModuleInterface;
use atc\WHx4\Core\ViewLoader;
//use atc\WHx4\Core\Traits\AppliesTitleArgs; // do this via posttypehandler instead

abstract class Module implements ModuleInterface
{
    //use AppliesTitleArgs;

    protected ?string $moduleSlug = null;
    abstract public function getPostTypeHandlers(): array;

    public function boot(): void
    {
		error_log( '=== Module class boot() ===' );
        $this->registerDefaultViewRoot();

		foreach ( $this->getPostTypeHandlers() as $handlerClass ) {
			error_log( 'About to attempt boot for handlerClass: ' . print_r($handlerClass,true) );
			if ( class_exists( $handlerClass ) ) {
				$handler = new $handlerClass();
				if ( method_exists( $handler, 'boot' ) ) {
					$handler->boot();
				}
			} else {
				error_log( "PostTypeHandler class not found: $handlerClass" );
			}
		}
    }

	public function getSlug(): string
	{
		return $this->detectModuleSlugFromNamespace();
	}

	// Human-readable label version of Module name
	public function getName(): string
	{
		$parts = explode( '\\', static::class );
		$name  = end( $parts ) === 'Module' && isset( $parts[ count( $parts ) - 2 ] )
			? $parts[ count( $parts ) - 2 ]
			: ( new \ReflectionClass( $this ) )->getShortName();

		return ucwords( str_replace( '_', ' ', $name ) );
	}


	protected function registerDefaultViewRoot(): void
	{
		$slug = $this->detectModuleSlugFromNamespace();

		if ( ! ViewLoader::hasViewRoot( $slug ) ) {
			ViewLoader::registerModuleViewRoot( $slug, __DIR__ . '/views' );
		}
	}

	protected function detectModuleSlugFromNamespace(): string
	{
		if ( isset( $this->moduleSlug ) ) {
			return $this->moduleSlug;
		}

		$parts = explode( '\\', static::class ); // Example: smith\Rex\Modules\Supernatural\Module → supernatural

		$key = array_search( 'Modules', $parts, true );
		if ( $key !== false && isset( $parts[ $key + 1 ] ) ) {
			$this->moduleSlug = strtolower( $parts[ $key + 1 ] );
		} else {
			// Fallback
			// ->getShortName() returns just the class name without the namespace
			$this->moduleSlug = strtolower( ( new \ReflectionClass( $this ) )->getShortName() );
		}

		return $this->moduleSlug;
	}

	public function getPostTypes(): array
	{
		error_log( '=== \Core\Module -- getPostTypes() ===' );
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

	public function renderView( string $view, array $vars = [] ): void
	{
		$this->plugin->renderView( $view, $vars, $this->getSlug() );
	}

    public function getViewPath( string $view ): ?string
	{
		return $this->plugin->getViewPath( $view, $this->getSlug() );
	}

	public function renderViewToString( string $view, array $vars = [] ): string
	{
		return $this->plugin->renderViewToString( $view, $vars, $this->getSlug() );
	}
}
