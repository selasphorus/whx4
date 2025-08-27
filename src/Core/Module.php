<?php

namespace atc\WHx4\Core;

use atc\WHx4\Plugin;
use atc\WHx4\Core\Contracts\ModuleInterface;
use atc\WHx4\Core\ViewLoader;

// TODO: make this final class?
abstract class Module implements ModuleInterface
{
    protected Plugin $plugin;
    protected ?string $moduleSlug = null;

    public function setPlugin( Plugin $plugin ): void
	{
		$this->plugin = $plugin;
	}

    /**
	 * @return array<class-string>
	 */
	 // TODO: consider finding these automatically, as with FieldGroups?
	abstract public function getPostTypeHandlerClasses(): array;
	/**
	 * @return array<class-string>
	 */
    //abstract public function getPostTypeHandlers(): array;
    public function getPostTypeHandlers(): array
	{
		return array_map(
			fn( $class ) => new $class(),
			$this->getPostTypeHandlerClasses()
		);
	}

    public function boot(): void
    {
		//error_log( '=== Module class boot() for module: ' . $this->getSlug() . '===' );
        $this->registerDefaultViewRoot();

        $enabledSlugs = $this->plugin
			->getSettingsManager()
			->getEnabledPostTypeSlugsByModule()[ $this->getSlug() ] ?? [];

		//error_log( 'enabledSlugs: ' . print_r($enabledSlugs,true) );

		// Get all the post type handlers for this module
		foreach ( $this->getPostTypeHandlerClasses() as $handlerClass ) {
			if ( ! class_exists( $handlerClass ) ) {
				error_log( "Missing post type handler: $handlerClass" );
				continue;
			}

			$handler = new $handlerClass();

			if ( ! method_exists( $handler, 'getSlug' ) ) {
				error_log( "Handler class $handlerClass missing getSlug()" );
				continue;
			}

			if ( ! in_array( $handler->getSlug(), $enabledSlugs, true ) ) {
				//error_log( 'slug: ' . $handler->getSlug() . ' is not in the enabledSlugs array: ' . print_r($enabledSlugs,true) );
				continue; // Skip if not enabled for this module
			}

			//error_log( 'About to attempt handler boot() for PostType handlerClass: ' . $handlerClass . '===' );
			if ( ! method_exists( $handler, 'boot' ) ) {
				error_log( "Handler class $handlerClass missing boot()" );
				continue;
			}
			$handler->boot();
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
			$reflector = new \ReflectionClass( $this );
			$moduleDir = dirname( $reflector->getFileName() );
			ViewLoader::registerModuleViewRoot( $slug, $moduleDir . '/Views' );
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

	// ?? obsolete/redundant
	public function getPostTypes(): array
	{
		error_log( '=== \Core\Module -- getPostTypes() ===' );
		$postTypes = [];

		foreach( $this->getPostTypeHandlerClasses() as $class ) {
			try {
				$handler = new $class();
				$slug = $handler->getSlug();
				$label = $handler->getLabels()['singular_name']; // or getLabel()
				$postTypes[ $slug ] = $label;
			} catch( \Throwable $e ) {
				error_log( "Error in post type handler {$class}: " . $e->getMessage() );
			}
		}

		//error_log("postTypes: " . print_r($postTypes, true));

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
