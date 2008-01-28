<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @package    Hlen
 */


class HCookie
{

    public static function read($var)
    {
        return $_COOKIE[$var];
    }

    public static function exists($var)
    {
        return isset($_COOKIE[$var]);
    }

    public static function write($var, $val)
    {
        if (headers_sent()) {
            Die("Nastaveni cookie nelze provest, hlavicky byly jiz odeslany.");
        }

        setcookie($var, $val, time() + HBasics::getVal(HConfigure::read('Cookie.expires'), 2419200));
        $_SESSION[$var] = $val;
    }

    public static function delete($var)
    {
        setcookie($var, '', time() - 60000);
    }

}
