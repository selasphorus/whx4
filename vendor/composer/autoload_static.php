<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit79e97093d8a36b936357e12844d9102a
{
    public static $prefixLengthsPsr4 = array (
        'a' => 
        array (
            'atc\\WHx4\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'atc\\WHx4\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit79e97093d8a36b936357e12844d9102a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit79e97093d8a36b936357e12844d9102a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit79e97093d8a36b936357e12844d9102a::$classMap;

        }, null, ClassLoader::class);
    }
}
