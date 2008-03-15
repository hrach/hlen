<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */


class HConfigure
{

    private static $config = array();


    /*
     * Zapise konfiguraci
     * 
     * @param	string	jmeno konfiguracni direktivy
     * @param	mixed	hodnota
     * @return	void
     */
    public static function write($var, $val)
    {
        HConfigure::$config[$var] = $val;
    }

    /*
     * Precte konfiguraci
     * Pokud neni direktiva dostupna (nebyla jeste nastavena), vrati metoda druhy argument
     * 
     * @param	string	jmeno direktivy
     * @param	mixed	vyhozi hodnota
     * @return	mixed
     */
    public static function read($var, $default = null)
    {
        if (isset(HConfigure::$config[$var])) {
            return HConfigure::$config[$var];
        } elseif (isset($default)) {
            return $default;
        } else {
            return false;
        }
    }

}