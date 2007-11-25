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

    /**
     * get ip
     *
     * @param void
     * @return string
     */
    public function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * get request method
     *
     * @param void
     * @return string
     */
    public function getRequestMethod()
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