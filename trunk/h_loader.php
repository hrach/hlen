<?php

/**
 * Hlen Framework
 *
 * Copyright (c) 2007 Jan -Hrach- Skrasek (http://hrach.netuje.cz)
 *
 * @author     Jan Skrasek
 * @copyright  Copyright (c) 2007 Jan Skrasek
 * @category   Hlen
 * @package    Hlen-Core
 */


class HLoader
{



    static public function getClasses($cacheFile, $scanDir)
    {
        if (!file_exists($scanDir)) {
            return array();
        }

        if (file_exists($cacheFile)) {
            $cache = file_get_contents($cacheFile);
            $cache = unserialize($cache);
        } else {
            $cache = self::makeCache($cacheFile, $scanDir);
        }

        return $cache;
    }

    static private function makeCache($cacheFile, $scanDir)
    {
        $classes = array();
        $files = self::getFiles($scanDir);
        foreach ($files as $file)
        {
            $content = file_get_contents($file);
            if(preg_match_all('/class(\s)+([a-zA-Z]+)(\s)*(extends(\s)+[a-zA-Z]+(\s)*)?(implements(\s)+[a-zA-Z]+(\s)*)?{[^}]/s', $content, $matches, PREG_SET_ORDER))
            {
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

    static private function getFiles($dir)
    {
        $dir = trim($dir, '/');

        $folder = new DirectoryIterator($dir);

        $files = array();
        foreach ($folder as $file )
        {
            if ($file->isDot()) continue;

            if ($file->isDir()) {
                $files = array_merge($files, self::getFiles($dir.'/'.$file->getFilename()));
            } elseif(preg_match("/.php$/",$file->getFilename())) {
                $files[] = $dir.'/'.$file->getFilename();
            }
        }

        return $files;
    }

}