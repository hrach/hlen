<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Obal pro konfiguraci
 *
 * Uchovava konfiguracni direktivy
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HConfigure
{

    /** @var array */
    private static $config = array();

    /**
     * Ulozi hodnotu
     *
     * @param string
     * @param mixed
     */
    public static function write($var, $val)
    {
        HConfigure::$config[$var] = $val;
    }

    /**
     * Vratin hodnotu konfiguracni direktivy
     *
     * @param string
     * @return mixed
     */
    public static function read($var)
    {
        if (isset(HConfigure::$config[$var])) {
            return HConfigure::$config[$var];
        } else {
            return false;
        }
    }

}