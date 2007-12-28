<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Obal pro zakladni funkce
 *
 * Funkce pro praci s textem, soubory, promennymi
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HBasics
{

    /**
     * Nacte (pouze existujici) soubor
     *
     * @param string   $fileName
     * @param boolean  $once = true
     * @return boolean
     */
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

    /**
     * Zkamelizuje retezec
     *
     * @param string $word
     * @return string
     */
    public static function camelize($word)
    {
        $camelWord = str_replace(' ', '', ucwords(str_replace('_', ' ', $word)));
        return $camelWord;
    }

    /**
     * Velka pismena prevede na mala a prida pred ne podtrzitka
     *
     * @param string $word
     * @return string
     */
    public static function underscore($word)
    {
        $underscoreWord = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $word));
        return $underscoreWord;
    }

    /**
     * Vytvori retezec vhodny pro url
     *
     * Odstrani diakritiku, mezery a spec. znaky nahradi pomlckami
     * @param string $title
     * @return string
    */
    public static function coolUrl($title) {
        $url = $title;
        $url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
        $url = trim($url, "-");
        $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
        $url = strtolower($url);
        $url = preg_replace('~[^-a-z0-9_]+~', '', $url);
        return $url;
    }

    /**
     * Vrati prvni neprazdny argument
     *
     * @param mixed
     * @return mixed
     */
    public static function getVal()
    {
        foreach (func_get_args() as $var) {
            if (!empty($var)) {
                return $var;
            }
        }
    }

}