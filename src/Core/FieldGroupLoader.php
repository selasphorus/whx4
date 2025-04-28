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
        //error_log( 'fieldsDir: ' . $fieldsDir );

        if ( !is_dir( $fieldsDir ) ) {
            return;
        }

        $activePostTypes = $this->plugin->getActivePostTypes();

        foreach ( glob( $fieldsDir . '/*Fields.php' ) as $file ) {
            require_once $file;

            $className = $this->getFullyQualifiedClassName( $file );
            error_log( 'className: ' . $className );

            if (
                class_exists( $className ) &&
                is_subclass_of( $className, FieldGroupInterface::class )
            ) {
                $basename = basename( $file, '.php' ); // e.g. "MonsterFields"
                $postType = strtolower( str_replace( 'Fields', '', $basename ) );
                error_log( 'basename: ' . $basename . '; postType: ' . $postType );

                if ( in_array( $postType, $activePostTypes, true ) ) {
                    $className::register();
                } elseif ( $this->isModuleFieldGroup( $basename, $moduleClass ) ) {
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
        $srcPath = str_replace( ['\\', '/'], DIRECTORY_SEPARATOR, dirname( __DIR__, 2 ) . '/' );
        $file = str_replace( ['\\', '/'], DIRECTORY_SEPARATOR, $file );

        $relativePath = str_replace( $srcPath, '', $file );
        $relativePath = str_replace( [DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath );

        return 'atc\\WHx4\\' . $relativePath;
    }


    /*protected function getFullyQualifiedClassName( string $filePath ): string
    {
        // Assumes PSR-4 autoloading and class name = file name
        $relativePath = str_replace( realpath( dirname( __DIR__, 2 ) ) . '/', '', realpath( $filePath ) );
        $parts = explode( '/', $relativePath );
        $parts = array_map( fn( $p ) => str_replace( '.php', '', $p ), $parts );
        return 'atc\whx4\\' . implode( '\\', $parts );
    }*/

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
