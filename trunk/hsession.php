<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */

HSession::start();


class HSession
{

    public static function start()
    {
        self::checkHeaders();
        self::init();
        session_start();
    }

    public static function read($var)
    {
        if (isset($_SESSION[$var])) {
            return $_SESSION[$var];
        } else {
            false;
        }
    }

    public static function exists($var)
    {
        return isset($_SESSION[$var]);
    }

    public static function write($var, $val)
    {
        $_SESSION[$var] = $val;
    }

    public static function delete($var)
    {
        unset($_SESSION[$var]);
    }

    public static function destroy()
    {
        session_destroy();
    }

    private static function init()
    {
        if (function_exists('ini_set')) {
            ini_set('session.use_cookies', 1);
            ini_set('session.name', HConfigure::read('Session.name', 'hlen-session'));
            ini_set('session.cookie_lifetime', HConfigure::read('Session.expires', 3600));
            ini_set('session.cookie_path', Hconfigure::read('Session.path', HHttp::getBase()));
            ini_set('session.cookie_domain', Hconfigure::read('Session.domain', HHttp::getDomain()));
            ini_set('session.save_path', Hconfigure::read('Session.temp', APP . 'temp'));
        }
    }

    private static function checkHeaders()
    {
        if (headers_sent()) {
            Die("Nelze nastavit session promennou, hlavicky byly jiz odeslany.");
        }
    }

}