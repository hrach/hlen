<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.5
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/hhttp.php';


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
            
            $sname    = 'hlen-session';
            $sexpires = 3600;
            $spath    = HHttp::getRealUrl();
            $sdomain  = HHttp::getDomain();
            
            if (class_exists('HApplication', false)) {
                ini_set('session.save_path', Hconfigure::read('Session.temp', APP . 'temp'));    
                $sname    = HConfigure::read('Session.name', $sname);
                $sexpires = HConfigure::read('Session.expires', $sexpires);
                $spath    = Hconfigure::read('Session.path', $spath);
                $sdomain  = Hconfigure::read('Session.domain', $sdomain);
            }

            if (substr_count ($sdomain, ".") == 1) {
                $sdomain = '.' . $sdomain;
            } else {
                $sdomain = preg_replace ('/^([^.])*/i', null, $sdomain);
            }

            ini_set('session.name', $sname);
            ini_set('session.cookie_lifetime', $sexpires);
            ini_set('session.cookie_path', $spath);
            ini_set('session.cookie_domain', $sdomain);
        }
    }

    private static function checkHeaders()
    {
        if (headers_sent()) {
            Die("Nelze nastavit session promennou, hlavicky byly jiz odeslany.");
        }
    }

}

HSession::start();