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

class HBasics
{

    /**
     * try load a file
     * @param string $fileName
     * @param boolean $once = true
     * @return boolean
     */
    function load($fileName, $once = true)
    {
        if(file_exists($fileName))
        {
            if($once)
                require_once($fileName);
            else
                require($fileName);
            return true;
        }
        return false;
    }

    public static function camelize($word)
    {
        $replace = str_replace(" ", "", ucwords(str_replace("_", " ", $word)));
        return $replace;
    }

    public static function underscore($word)
    {
        $replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $word));
        return $replace;
    }

    /**
     * make cool-url
     *
     * @param  string  $title string in UTF-8
     * @return string
    */
    function coolUrl($title) {
        $url = $title;
        $url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
        $url = trim($url, "-");
        $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
        $url = strtolower($url);
        $url = preg_replace('~[^-a-z0-9_]+~', '', $url);
        return $url;
    }

    public static function getVal()
    {
        foreach(func_get_args() as $var)
            if(!empty($var))
                return $var;
    }

}