<?php

namespace atc\WHx4\Core;

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
        foreach( $this->plugin->getActiveModules() as $module ) {
            $this->registerFieldsForModule( $module );
        }
    }

	protected function registerFieldsForModule( string $moduleClass ): void
	{
		$ref = new \ReflectionClass( $moduleClass );
		$moduleDir = dirname( $ref->getFileName() );
		$fieldsDir = $moduleDir . '/Fields';
	
		if ( !is_dir( $fieldsDir ) ) {
			return;
		}
	
		$activePostTypes = $this->plugin->getActivePostTypes();
	
		foreach ( glob( $fieldsDir . '/*Fields.php' ) as $file ) {
			require_once $file;
	
			$className = $this->getFullyQualifiedClassName( $file );
	
			if (
				class_exists( $className ) &&
				is_subclass_of( $className, FieldGroupInterface::class )
			) {
				$basename = basename( $file, '.php' ); // e.g. "MonsterFields"
				$postType = strtolower( str_replace( 'Fields', '', $basename ) );
	
				if ( in_array( $postType, $activePostTypes, true ) ) {
					$className::register();
				}
			}
		}
	}

    protected function getFullyQualifiedClassName( string $filePath ): string
    {
        // Assumes PSR-4 autoloading and class name = file name
        $relativePath = str_replace( realpath( dirname( __DIR__, 2 ) ) . '/', '', realpath( $filePath ) );
        $parts = explode( '/', $relativePath );
        $parts = array_map( fn( $p ) => str_replace( '.php', '', $p ), $parts );
        return 'YourPlugin\\' . implode( '\\', $parts );
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
