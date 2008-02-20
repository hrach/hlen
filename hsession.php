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
            ini_set('session.name', HBasics::getNonEmpty(HConfigure::read('Session.cookie'), 'hlen-session'));
            ini_set('session.cookie_lifetime', HBasics::getNonEmpty(HConfigure::read('Session.lifeTime'), 60*30));
            ini_set('session.save_path', APP . 'temp');
        }
    }

    private static function checkHeaders()
    {
        if (headers_sent()) {
            Die("Presmerovani nelze provest, hlavicky byly jiz odeslany.");
        }
    }

}