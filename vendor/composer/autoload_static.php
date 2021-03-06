<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9ee9bef0ac78d99cccd7ebddca179171
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'OBSER\\WPB_Components\\' => 21,
            'OBSER\\Shortcodes\\' => 17,
            'OBSER\\Config\\' => 13,
            'OBSER\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'OBSER\\WPB_Components\\' => 
        array (
            0 => __DIR__ . '/../..' . '/plugins-support/Components',
        ),
        'OBSER\\Shortcodes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Shortcodes',
        ),
        'OBSER\\Config\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Config',
        ),
        'OBSER\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Core',
        ),
    );

    public static $classMap = array (
        'OBSER\\Classes\\Api\\Endpoint' => __DIR__ . '/../..' . '/Core/Classes/Api/Endpoint.php',
        'OBSER\\Classes\\Arr' => __DIR__ . '/../..' . '/Core/Classes/Arr.php',
        'OBSER\\Classes\\Component' => __DIR__ . '/../..' . '/Core/Classes/Component.php',
        'OBSER\\Classes\\Helpers' => __DIR__ . '/../..' . '/Core/Classes/Helpers.php',
        'OBSER\\Classes\\Mails\\Controller' => __DIR__ . '/../..' . '/Core/Classes/Mails/Controller.php',
        'OBSER\\Classes\\Metabox\\Field' => __DIR__ . '/../..' . '/Core/Classes/Metabox/Field.php',
        'OBSER\\Classes\\Metabox\\Metabox' => __DIR__ . '/../..' . '/Core/Classes/Metabox/Metabox.php',
        'OBSER\\Classes\\Metabox\\Tab' => __DIR__ . '/../..' . '/Core/Classes/Metabox/Tab.php',
        'OBSER\\Classes\\Settings' => __DIR__ . '/../..' . '/Core/Classes/Settings.php',
        'OBSER\\Classes\\Shortcode' => __DIR__ . '/../..' . '/Core/Classes/Shortcode.php',
        'OBSER\\Shortcodes\\Gallery' => __DIR__ . '/../..' . '/Shortcodes/Gallery.php',
        'OBSER\\Shortcodes\\_Grid' => __DIR__ . '/../..' . '/Shortcodes/_Grid.php',
        'OBSER\\WPB_Components\\_Grid' => __DIR__ . '/../..' . '/plugins-support/Components/_Grid.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9ee9bef0ac78d99cccd7ebddca179171::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9ee9bef0ac78d99cccd7ebddca179171::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9ee9bef0ac78d99cccd7ebddca179171::$classMap;

        }, null, ClassLoader::class);
    }
}
