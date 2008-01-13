<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */

HHttp::sanitizeData();


/**
 * Trida pro praci s hlavickou
 *
 * Trida poskytuje efektivni metody pro praci se zaslanymi daty
 * Data automaticky osetri
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.1
 */
class HHttp
{

    /**
     * Osetreni vsech vstupnich data od prebytecnych apostrofu
     */
    public static function sanitizeData()
    {
        if (get_magic_quotes_gpc()) {
            $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST, &$_FILES);
            while (list($key, $val) = each($process)) {
                foreach ($val as $k => $v) {
                    unset($process[$key][$k]);
                    if (is_array($v)) {
                        $process[$key][stripslashes($k)] = $v;
                        $process[] = &$process[$key][stripslashes($k)];
                    } else {
                        $process[$key][stripslashes($k)] = stripslashes($v);
                    }
                }
            }
            unset($process);
        }
    }

    /**
     * Vrati IP adresu
     *
     * @return string
     */
    public static function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Vrati metodu pozadavku
     *
     * @return string
     */
    public static function getRequestMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Vrati zakladni url aplikace
     *
     * @return string
     */
    public static function getBase()
    {
        $base = HHttp::sanitizeUrl(dirname($_SERVER['PHP_SELF']));

        if (empty($base)) {
            return '/';
        } else {
            return '/' . $base . '/';
        }
    }

    /**
     * Vrati kompletni url aplikace
     *
     * @return string
     */
    public static function getUrl()
    {
        $url  = 'http:' . ($_SERVER['HTTPS'] ? 's' : '') . '//'
              . $_SERVER['SERVER_NAME']
              . HHttp::getBase();

        return $url;
    }

    /**
     * Presmeruje na danou url
     *
     * @todo dodelat graficky vystup - headers_sent()
     * @param string $absoluteUrl
     * @return void
     */
    public static function redirect($absoluteUrl)
    {
        if (headers_sent()) {
            Die("Presmerovani nelze provest, hlavicky byly jiz odeslany.");
        }

        Header('Location: '. $absoluteUrl);
    }

    /**
     * Vrati _POST data
     *
     * @param string $var = null
     * @return mixed|array
     */
    public static function getPost($var = null)
    {
        if ($var) {
            return $_POST[$var];
        } else {
            return $_POST;
        }
    }

    /**
     * Vrati _GET data
     *
     * @param string $var = null
     * @return mixed|array
     */
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

    /**
     * Odstrani ze zacatku a konce url prebytecna lomitka
     *
     * @param string $url
     * @return string
     */
    public static function sanitizeUrl($url)
    {
        return trim($url, '/');
    }

    /**
     * Prevede url na pole
     *
     * @param string $url
     * @return array
     */
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