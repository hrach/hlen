<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/happlication.php';

HAutoLoad::registerAutoLoad();


class HAutoLoad
{

    public static $scanDir = "";

    public static $cacheFile = "./temp/classes.cache";

    public static $list = array();

    public static $coreFiles = array(
        'hbasics', 'hconfigure', 'hdb', 'hcookie', 'hdebug', 'hform',
        'hhttp', 'hloader', 'hrouter', 'hsession',  'hhtml', 'hcontroller'
    );


    public static function registerAutoload()
    {
        self::makeClassesList();
        spl_autoload_register(array('HAutoLoad', 'autoload'));
    }

    public static function autoload($className)
    {
        $className = strtolower($className);

        if (isset(self::$list[$className])) {
            require_once self::$list[$className];
        } elseif (in_array($className, self::$coreFiles)) {
            require_once CORE . $className . '.php';
        }
    }

    private static function makeClassesList()
    {
        if (!file_exists(APP . self::$scanDir)) {
            return 0;
        }

        if (self::$cacheFile !== false && file_exists(APP . self::$cacheFile)) {
            $cache = file_get_contents(APP . self::$cacheFile);
            self::$list = unserialize($cache);
        } else {
            self::findClasses();
        }

        if (self::$cacheFile !== false && !file_exists(APP . self::$cacheFile) && file_exists(APP . dirname(self::$cacheFile))) {
            file_put_contents(APP . self::$cacheFile, serialize(self::$list));
        }
    }

     private static function findClasses()
     {
        $files = self::getFiles(APP . self::$scanDir);

        foreach ($files as $file) {
            self::getClasses($file);
        }
    }

    private static function getFiles($dir)
    {
        $folder = new DirectoryIterator($dir);
        $files = array();

        foreach ($folder as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isDir()) {
                $files = array_merge($files,  self::getFiles($dir . '/' . $file->getFilename()));
            } elseif (preg_match('/.php$/', $file->getFilename())) {
                $files[] = $dir . '/' . $file->getFilename();
            }
        }

        return $files;
    }

    private static function getClasses($file) {
        $catch = false;

        foreach (token_get_all(file_get_contents($file)) as $token) {
            if (is_array($token)) {
                if ($token[0] == T_CLASS || $token[0] == T_INTERFACE) {
                    $catch = true;
                } elseif ($token[0] == T_STRING && $catch) {
                    self::$list[strtolower($token[1])] = $file;
                    $catch = false;
                }
            }
        }
    }

}