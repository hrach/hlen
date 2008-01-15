<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


class HBasics
{

    public static function load($fileName, $once = true)
    {
        if (file_exists($fileName)) {
            if ($once) {
                require_once($fileName);
            } else {
                require($fileName);
            }
            return true;
        }
        return false;
    }

    public static function camelize($word)
    {
        $camelWord = str_replace(' ', '', ucwords(str_replace('_', ' ', $word)));
        return $camelWord;
    }

    public static function underscore($word)
    {
        $underscoreWord = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $word));
        return $underscoreWord;
    }

    public static function coolUrl($title) {
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
        foreach (func_get_args() as $var) {
            if (!empty($var)) {
                return $var;
            }
        }
    }

}