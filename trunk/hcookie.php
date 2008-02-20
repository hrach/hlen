<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
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
        self::checkHeaders();

        setcookie($var, $val, time() + HBasics::getNonEmpty(HConfigure::read('Cookie.expires'), 2419200));
        $_SESSION[$var] = $val;
    }

    public static function delete($var)
    {
        setcookie($var, '', time() - 60000);
    }

    private static function chceckHeaders()
    {
        if (headers_sent()) {
            Die("Presmerovani nelze provest, hlavicky byly jiz odeslany.");
        }
    }

}