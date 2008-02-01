<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @package    Hlen
 */


class HLoader
{

    public static function getClasses($scanDir, $cacheFile, $recursive = true)
    {
        if (!file_exists($scanDir)) {
            return array();
        }

        if ($cacheFile !== false && file_exists($cacheFile)) {
            $cache = file_get_contents($cacheFile);
            $cache = unserialize($cache);
        } else {
            $cache = self::makeCache($scanDir, $cacheFile, $recursive);
        }

        return $cache;
    }

     private static function makeCache($scanDir, $cacheFile, $recursive)
     {
        $classes = array();
        $files = self::getFiles($scanDir, $recursive);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/class(\s)+([a-zA-Z]+)(\s)*(extends(\s)+[a-zA-Z]+(\s)*)?(implements(\s)+[a-zA-Z]+(\s)*)?{[^}]/s',
                               $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $classes[$match[2]] = $file;
                }
            }
        }

        if (file_exists(dirname($cacheFile))) {
            file_put_contents($cacheFile, serialize($classes));
        }

        return $classes;
    }

    private static function getFiles($dir, $recursive)
    {
        $folder = new DirectoryIterator($dir);
        $files = array();

        foreach ($folder as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isDir() && $recursive) {
                $files = array_merge(
                    $files,
                    self::getFiles($dir . '/' . $file->getFilename(), $recursive)
                );
            } elseif (preg_match('/.php$/', $file->getFilename())) {
                $files[] = $dir . '/' . $file->getFilename();
            }
        }

        return $files;
    }

}