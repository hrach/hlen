<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.5
 * @package    Hlen
 */


class HCookie
{

    public static function read($var)
    {
        if (isset($_COOKIE[$var])) {
            return $_COOKIE[$var];
        } else {
            return false;
        }
    }

    public static function exists($var)
    {
        return isset($_COOKIE[$var]);
    }

    public static function write($var, $val, $path = null, $domain = null)
    {
        self::checkHeaders();

        $expires = 3600;
        if (class_exists('HApplication', false)) {
            $expires = HConfigure::read('Cookie.expires', $expires);
        }

        setcookie($var, $val, time() + $expires, $path, $domain);
    }

    public static function delete($var, $path = null, $domain = null)
    {
        self::checkHeaders();
        setcookie($var, false, time() - 60000, $path, $domain);
    }

    private static function checkHeaders()
    {
        if (headers_sent()) {
            Die("Nelze nastavit cookie, hlavicky byly jiz odeslany.");
        }
    }

}