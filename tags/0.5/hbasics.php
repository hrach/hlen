<?php

/**
 * HLEN FRAMEWORK
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5 $WCREV$
 * @package     Hlen
 */


/**
 * Trida HBasics poskytuje casto pouzivane funkce, zatim v oblasti retezcu
 */
class HBasics
{

    /**
     * Kamelizuje retezec
     *
     * @param   string  retezec, ktery chcete kamelizovat
     * @return  string
     */
    public static function camelize($word)
    {
        $camelWord = str_replace(' ', '', ucwords(str_replace('_', ' ', $word)));
        return $camelWord;
    }

    /**
     * Velka pismena prevede na male a vlozi pred ne podtrzitko
     *
     * @param   string  retezec, ktery chcete prevest
     * @return  string
     */
    public static function underscore($word)
    {
        $underscoreWord = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $word));
        return $underscoreWord;
    }

    /**
     * Vytvori z retezce jeho reprezentaci vhodnou pro url
     * Preved vsechny znaky na mala pismena, vsechny ne-alfanumericke znaky nahradi pomlckou;
     *      odstrani pripadne pomlcky za sebou
     * U stare knihovny glic v inconv exstenzi nefungoval spravne prevod na ascii;
     *      metoda proto obsahuje kontrolu, a pokud neni dostupna moderni knihovna libiconv,
     *      prevede retezec na ascii sama; tento zpusob ale funguje pouze pro ceske a slovenske znaky
     *
     * @param   string  retezec, ktery chcete prevest
     * @return  string
     */
    public static function coolUrl($title) {
        $title = preg_replace('~[^\\pL0-9_]+~u', '-', $title);
        $title = trim($title, "-");
        if (defined('ICONV_IMPL') && ICONV_IMPL != 'libiconv') {

            // author David GRUDL
            // site http://www.davidgrudl.cz/
            static $tbl = array("\xc3\xa1"=>"a","\xc3\xa4"=>"a","\xc4\x8d"=>"c","\xc4\x8f"=>"d","\xc3\xa9"=>"e","\xc4\x9b"=>"e","\xc3\xad"=>"i","\xc4\xbe"=>"l","\xc4\xba"=>"l","\xc5\x88"=>"n","\xc3\xb3"=>"o","\xc3\xb6"=>"o","\xc5\x91"=>"o","\xc3\xb4"=>"o","\xc5\x99"=>"r","\xc5\x95"=>"r","\xc5\xa1"=>"s","\xc5\xa5"=>"t","\xc3\xba"=>"u","\xc5\xaf"=>"u","\xc3\xbc"=>"u","\xc5\xb1"=>"u","\xc3\xbd"=>"y","\xc5\xbe"=>"z","\xc3\x81"=>"A","\xc3\x84"=>"A","\xc4\x8c"=>"C","\xc4\x8e"=>"D","\xc3\x89"=>"E","\xc4\x9a"=>"E","\xc3\x8d"=>"I","\xc4\xbd"=>"L","\xc4\xb9"=>"L","\xc5\x87"=>"N","\xc3\x93"=>"O","\xc3\x96"=>"O","\xc5\x90"=>"O","\xc3\x94"=>"O","\xc5\x98"=>"R","\xc5\x94"=>"R","\xc5\xa0"=>"S","\xc5\xa4"=>"T","\xc3\x9a"=>"U","\xc5\xae"=>"U","\xc3\x9c"=>"U","\xc5\xb0"=>"U","\xc3\x9d"=>"Y","\xc5\xbd"=>"Z");
            $title = strtr($title, $tbl);
        } else {

            // author Jakub VRANA
            // site http://php.vrana.cz
            $title = iconv("utf-8", "us-ascii//TRANSLIT", $title);
        }
        $title = strtolower($title);
        $title = preg_replace('~[^-a-z0-9_]+~', '', $title);
        return $title;
    }

}