<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


class HConfigure
{

    private static $config = array();


    public static function write($var, $val)
    {
        HConfigure::$config[$var] = $val;
    }

    public static function read($var)
    {
        if (isset(HConfigure::$config[$var])) {
            return HConfigure::$config[$var];
        } else {
            return false;
        }
    }

}