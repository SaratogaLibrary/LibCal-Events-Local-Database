<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit814e0fc1f14fe2e03625c6eee36eb2fc
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PhpOffice\\PhpWord\\' => 18,
            'PhpOffice\\Math\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PhpOffice\\PhpWord\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpoffice/phpword/src/PhpWord',
        ),
        'PhpOffice\\Math\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpoffice/math/src/Math',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit814e0fc1f14fe2e03625c6eee36eb2fc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit814e0fc1f14fe2e03625c6eee36eb2fc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit814e0fc1f14fe2e03625c6eee36eb2fc::$classMap;

        }, null, ClassLoader::class);
    }
}
