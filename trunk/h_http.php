<?php

/**
 * Hlen Framework
 *
 * Copyright (c) 2007 Jan -Hrach- Skrasek (http://hrach.netuje.cz)
 *
 * @author     Jan Skrasek
 * @copyright  Copyright (c) 2007 Jan Skrasek
 * @category   Hlen
 * @package    Hlen-Core
 */


class HHttp
{

    /** @var boolean */
    private static $sanitize = false;

    /**
     * sanitize data
     *
     * @param void
     * @return void
     */
    private static function sanitizeData()
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
     * get ip
     *
     * @param void
     * @return string
     */
    public static function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * get request method
     *
     * @param void
     * @return string
     */
    public static function getRequestMethod()
    {
        return strtolower( $_SERVER['REQUEST_METHOD'] );
    }

    /**
     * return application url
     *
     * @param void
     * @return string
     */
    public static function getBase()
    {
        $base = HHttp::sanitizeUrl( dirname( $_SERVER['PHP_SELF'] ));
        if(empty($base))
            return '/';
        else
            return  '/'. $base .'/';
    }

    /**
     * return application url
     *
     * @param void
     * @return string
     */
    public static function getUrl()
    {
        $url = 'http:' . ($_SERVER['HTTPS'] ? 's' : '' ) .'//';
        $url .= $_SERVER['SERVER_NAME'];
        $url .= HHttp::getBase();

        return $url;
    }

    /**
     * redirect to new url
     *
     * @param string $absoluteUrl
     * @return void
     */
    public static function redirect($absoluteUrl)
    {
        Header('Location: '.$absoluteUrl);
    }

    /**
     * return post data
     *
     * @param string $var
     * @return string
     */
    public static function getPost($var = null)
    {
        if(HHttp::$sanitize === false) HHttp::sanitizeData();

        if($var)
            return $_POST[$var];
        else
            return $_POST;
    }

    /**
     * return get data
     *
     * @param string $var
     * @return string
     */
    public static function getGet($var = null)
    {
        if(HHttp::$sanitize === false) HHttp::sanitizeData();

        if($var)
            return $_GET[$var];
        else
            return $_GET;
    }


    /**
     * trim '/' from string
     *
     * @param string $url
     * @return string
     */
    public static function sanitizeUrl($url)
    {
        $url = trim($url, '/');
        return $url;
    }

    /**
     * convert url string to array
     *
     * @param string $url
     * @return array
     */
    public static function urlToArray($url)
    {
        if(!empty($url))
            return explode('/', $url);
        else
            return array();
    }
}