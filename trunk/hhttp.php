<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @package    Hlen
 */

HHttp::sanitizeData();


class HHttp
{

    public static function sanitizeData()
    {
        if (get_magic_quotes_gpc()) {
            
            $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
            while (list($key, $val) = each($process)) {
                foreach ($val as $k => $v) {
                    unset($process[$key][$k]);
                    if (is_array($v)) {
                        $process[$key][$k] = $v;
                        $process[] = &$process[$key][$k];
                    } else {
                        $process[$key][$k] = stripslashes($v);
                    }
                }
            }
            unset($process);
        }
    }

    public static function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function getRequestMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function getBase()
    {
        $base = HHttp::sanitizeUrl(dirname($_SERVER['PHP_SELF']));

        if (empty($base)) {
            return '/';
        } else {
            return '/' . $base . '/';
        }
    }

    public static function getUrl()
    {
        $url  = 'http:' . ($_SERVER['HTTPS'] ? 's' : '') . '//'
              . $_SERVER['SERVER_NAME']
              . HHttp::getBase();

        return $url;
    }

    public static function redirect($absoluteUrl)
    {
        if (headers_sent()) {
            Die("Presmerovani nelze provest, hlavicky byly jiz odeslany.");
        }

        Header('Location: '. $absoluteUrl);
    }

    public static function getPost($var = null)
    {
        if (isset($_POST[$var])) {
            return $_POST[$var];
        } elseif(!isset($var)) {
            return $_POST;
        } else {
            return null;
        }
    }

    public static function getGet($var = null)
    {
        if (isset($_GET[$var])) {
            return $_GET[$var];
        } elseif(!isset($var)) {
            return $_GET;
        } else {
            return null;
        }
    }

    public static function sanitizeUrl($url)
    {
        return trim($url, '/');
    }

    public static function urlToArray($url)
    {
        $url = self::sanitizeUrl($url);

        if (!empty($url)) {
            return explode('/', $url);
        } else {
            return array();
        }
    }
}