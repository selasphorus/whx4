<?php

namespace atc\WHx4\Core;

use atc\WHx4\Plugin;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use atc\WHx4\Core\Contracts\FieldGroupInterface;

class FieldGroupLoader
{
    protected Plugin $plugin;

    public function __construct( Plugin $plugin )
    {
        $this->plugin = $plugin;
    }

    public function registerAll(): void
    {
        error_log( '=== registerAll field groups ===' );
        foreach( $this->plugin->getActiveModules() as $moduleClass ) {
            $this->registerFieldsForModule( $moduleClass );
        }
    }

    protected function registerFieldsForModule( string $moduleClass ): void
    {
        error_log( '=== registerFieldsForModule for moduleClass: ' . $moduleClass . ' ===' );
        $ref = new \ReflectionClass( $moduleClass );
        $moduleDir = dirname( $ref->getFileName() );
        $fieldsDir = $moduleDir . '/Fields';

        if ( !is_dir( $fieldsDir ) ) {
            error_log( 'fieldsDir: ' . $fieldsDir . 'not found. Aborting registration.' );
            return;
        }

        $activePostTypes = $this->plugin->getActivePostTypes();
        //error_log( 'activePostTypes: ' . print_r($activePostTypes, true) );

        // === Build a map of postType slug => short class name (e.g. rex_event => Event)
        $slugMap = [];

        // Instantiate the module class
        $module = new $moduleClass();

        if ( method_exists( $moduleClass, 'getPostTypeHandlerClasses' ) ) {
            foreach ( $module->getPostTypeHandlerClasses() as $handlerClass ) {
                if ( !class_exists( $handlerClass ) ) {
                    continue;
                }

                // Try to reflect default config
                $handlerSlug = null;

                try {
                    $reflection = new \ReflectionClass( $handlerClass );
                    $props = $reflection->getDefaultProperties();

                    if ( isset( $props['config']['slug'] ) ) {
                        $handlerSlug = $props['config']['slug'];
                    }
                } catch ( \ReflectionException ) {
                    // fall back to instantiation
                }

                // Fallback: instantiate handler only if needed
                if ( !$handlerSlug ) {
                    try {
                        $handler = new $handlerClass();
                        $handlerSlug = $handler->getSlug();
                    } catch ( \Throwable ) {
                        continue;
                    }
                }

                if ( $handlerSlug ) {
                    $shortName = basename( str_replace( '\\', '/', $handlerClass ) );
                    $slugMap[ $handlerSlug ] = $shortName;
                }
            }
        }

        // === Scan for field files
        foreach ( glob( $fieldsDir . '/*Fields.php' ) as $file ) {
            require_once $file;

            $className = $this->getFullyQualifiedClassName( $file );
            error_log( 'className: ' . $className );

            if (
                class_exists( $className ) &&
                is_subclass_of( $className, FieldGroupInterface::class )
            ) {
                $basename = basename( $file, '.php' ); // e.g. "MonsterFields"
                $shortName = str_replace( 'Fields', '', $basename ); // e.g. "Monster"
                error_log( 'basename: ' . $basename . '; shortName: ' . $shortName );

                $matched = false;

                foreach ( $slugMap as $slug => $expectedName ) {
                    if ( strtolower( $shortName ) === strtolower( $expectedName ) ) {
                        if ( array_key_exists( $slug, $activePostTypes ) ) {
                            error_log( 'about to register (via slugMap): ' . $className );
                            $className::register();
                            $matched = true;
                            break;
                        }
                    }
                }

                if ( !$matched && $this->isModuleFieldGroup( $basename, $moduleClass ) ) {
                    error_log( 'about to register (via slugMap): ' . $className );
                    $className::register();
                }
            }
        }
    }

    protected function isModuleFieldGroup( string $basename, string $moduleClass ): bool
    {
        $moduleBaseName = ( new \ReflectionClass( $moduleClass ) )->getShortName();
        $expectedName = $moduleBaseName . 'Fields';

        return $basename === $expectedName;
    }

    protected function getFullyQualifiedClassName( string $file ): string
    {
        // Get path for "src" dir
        $srcPath = dirname( __DIR__, 2 ) . '/src/';

        // Normalize slashes
        $file = str_replace( ['\\', '/'], DIRECTORY_SEPARATOR, $file );
        $srcPath = str_replace( ['\\', '/'], DIRECTORY_SEPARATOR, $srcPath );

        // Remove everything before "src/"
        $relativePath = str_replace( $srcPath, '', $file );

        // Replace directory separators with backslashes and strip ".php"
        $relativePath = str_replace( [DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath );

        return 'atc\\WHx4\\' . $relativePath;
    }

    /*
    public static function registerAll(): void {
        $basePath = __DIR__ . '/../Modules/';
        $baseNamespace = 'atc\WHx4\\Modules\\';
        $activePostTypes = PostTypeRegistrar::getActivePostTypes();

        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath)),
            '/Fields\/.+\.php$/',
            RegexIterator::GET_MATCH
        );

        foreach( $iterator as $files ) {
            foreach( $files as $file ) {
                require_once $file;

                $relativePath = str_replace([$basePath, '.php'], '', $file);
                $classPath = str_replace('/', '\\', $relativePath);
                $fqcn = $baseNamespace . $classPath;

                if(
                    class_exists($fqcn) &&
                    is_subclass_of($fqcn, FieldGroupInterface::class)
                ) {
                    $groupPostTypes = $fqcn::getPostTypes();
                    if( array_intersect($groupPostTypes, $activePostTypes) ) {
                        $fqcn::register();
                    }
                }
            }
        }
    }
    */
}
