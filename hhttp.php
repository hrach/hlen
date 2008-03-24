<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.5
 * @package    Hlen
 */


class HHttp
{

    /*
     * Pokud je treba, odstrani automaticky magic quotes
     * 		z $_GET, $_POST, $_COOKIE a $_REQUEST
     *
     * @return	void
     */
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

    /*
     * Zjisti, zda je volana stranka ajaxem
     *
     * @return	boolean
     */
    public static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            return true;
        }
        return false;
    }

    /*
     * Vrati IP uzivatele
     *
     * @return	string
     */
    public static function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /*
     * Vrati metodu pozadavku
     *
     * @return	string
     */
    public static function getRequestMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /*
     * Vrati zakladni url pro tvorbu odkazu
     * Url se vztahuje JEN vuci aplikace na serveru.
     * Pro zakladni url na css a jine soubory, tedy url ne-vedoucí do aplikace, využijte metodu HHttp::getRealUrl()
     *
     * Priklady se zapnutym mod_rewrite:
     *              aplikace bezi na				base url
     *              ----------------------------------------
     *              example.com						/
     *              test.example.com				/
     *              example.com/test				/test/
     *
     * Priklady bez mod_rewrite:
     *              example.com/index.php           /index.php/
     *              example.com/test/framework.php  /test/framework.php/
     * 
     * @return  string
     */
    public static function getBaseUrl()
    {
        $app = class_exists('HApplication', false); 
        if ($app) {
            $rewrite = HConfigure::read('Core.mod.rewrite', true);
        } else {
            $rewrite = false;
        }

        if ($rewrite) {
            $base = HHttp::sanitizeUrl(dirname($_SERVER['SCRIPT_NAME']));
        } else {
            $base = HHttp::sanitizeUrl($_SERVER['SCRIPT_NAME']);
        }

        if (empty($base)) {
            return '/';
        } else {
            if ($app) {
                return '/' . $base . '/';
            } else {
                return '/' . $base;
            }
        }
    }
    
    public static function getRealUrl()
    {
        $base = HHttp::sanitizeUrl(dirname($_SERVER['SCRIPT_NAME']));
        
        if (empty($base)) {
            return '/';
        } else {
            return '/' . $base . '/';
        }
    }

    /*
     * Vrati domenove jmeno / jmeno serveru
     *
     * @return	string
     */
    public static function getDomain()
    {
        return $_SERVER['SERVER_NAME'];
    }

    /*
     * Vrati absolutni base url
     *
     * @return	string
     */
    public static function getUrl()
    {
        $url  = 'http:' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '//'
              . self::getDomain()
              . self::getBaseUrl();

        return $url;
    }

    /*
     * Zasle presmerovaci hlavicku
     *
     * @param	string	absolutni url, na ktere chcete presmerovat
     * @param	integer	cislo presmerovaci hlavicky
     * @return	void
     */
    public static function headerRedirect($absoluteUrl, $code = 303)
    {
        self::checkHeaders();
        static $supportCode = array(300, 301, 302, 303, 304, 307);

        if (!in_array($code, $supportCode)) {
            die("Nepodporovany typ presmerovani.");
        }

        header('Location: ' . $absoluteUrl, true, $code);
    }

    /*
     * Zasle chybovou hlavicku
     *
     * @param	integer	cislo chybove hlavicky
     * @return	void
     */
    public static function headerError($code = 404)
    {
        self::checkHeaders();
        switch ($code) {
            case 401:
                header('HTTP/1.1 401 Unauthorized');
            break;
            case 404:
                header('HTTP/1.1 404 Not Found');
            break;
            case 500:
                header('HTTP/1.1 500 Internal Server Error');
            break;
            default:
                die("Nepodporovany typ chybove hlavicky.");
            break;
        }
    }

    /*
     * Vrati hodnotu promenne predane pomoci post,
     * 		nebo pokud nezadate nazev promennet vraci pole vsech parametru
     *
     * @param	string	jmeno promenne
     * @return	mixed
     */
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

    /*
     * Vrati hodnotu promenne predane pomoci get,
     * 		nebo pokud nezadate nazev promennet vraci pole vsech parametru
     *
     * @param	string	jmeno promenne
     * @return	mixed
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

    /*
     * Vraci routovaci url
     *
     * @return	string
     */
    public static function getRequestUrl()
    {
        $url = $_SERVER['REQUEST_URI'];
        $base = dirname($_SERVER['SCRIPT_NAME']);
        $baseFile = basename($_SERVER['SCRIPT_NAME']);
        if (substr($url, 0, strlen($base)) == $base) {
            $url = substr($url, strlen($base));
        }
        $url = self::sanitizeUrl($url);
        if (substr($url, 0, strlen($baseFile)) == $baseFile) {
            $url = substr($url, strlen($baseFile));
        }
        return $url;
    }

    /*
     * Osetri url, aby na zacatku a na konci nebyly lomitka
     *
     * @param	string	url
     * @return	string
     */
    public static function sanitizeUrl($url)
    {
        return trim($url, '/');
    }

    /*
     * Prevede url na pole, jednotlive prvky url jsou rozdeleny pomoci lomitek
     *
     * @param	string	url
     * @return	array
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

    /*
     * Zkontroluje, zda nebyly odeslany hlavicky
     * V pripade ze ano, script ukonci a vypise chybovou hlasku
     *
     * @return	void
     */
    private static function checkHeaders()
    {
        if (headers_sent()) {
            die("Presmerovani nelze provest, hlavicky byly jiz odeslany.");
        }
    }

}

HHttp::sanitizeData();