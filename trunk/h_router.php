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


final class HRouter {

    /** @var string */
    private static $url;
    /** @var boolean */
    private static $routing = false;

    /** @var array */
    private static $parseUrl = array();
    /** @var array */
    private static $reserved = array(':controller', ':action', ':arg');

    /** @var array */
    private static $services = array();
    /** @var string */
    public static $service;

    /** @var boolean */
    public static $allowDefault = true;

    /** @var string */
    public static $action;
    /** @var string */
    public static $controller;
    /** @var array */
    public static $args = array();
    /** @var boolean */
    public static $system = false;

    /**
     * start routing
     *
     * @param void
     * @return void
     */
    public static function start()
    {
        HRouter::$url = HHttp::urlToArray( HHttp::sanitizeUrl( HHttp::getGet('url') ));

        load(APP.'config/router.php');

        if(HRouter::$allowDefault)
        {
            HRouter::connect('/:controller/:action', array('args' => true));
            HRouter::connect('/:controller');
        }
    }

    /**
     * reserver services names in url
     *
     * @param array $services
     * @return void
     */
    public static function mapService($services)
    {
        HRouter::$services = array_merge(HRouter::$services, (array)$services);
    }

    /**
     * rewrite url
     *
     * @param string $rule
     * @param string $newUrl
     * @return boolean
     */
    public static function rewrite($rule, $newUrl)
    {
        $url = HHttp::sanitizeUrl( HHttp::getGet('url') );
        $rule = HHttp::sanitizeUrl($rule);
        $newUrl = HHttp::sanitizeUrl($newUrl);
        if($url === $rule)
        {
            HRouter::$url = HHttp::urlToArray($newUrl);
            return true;
        }
        return false;
    }

    /**
     * route the rule
     *
     * @param string $rule
     * @param array $options
     * @return boolean
     */
    public static function connect($rule, $options = array())
    {
        if(HRouter::$routing) return false;

        $router['action'] = 'index';
        $rule = HHttp::urlToArray(HHttp::sanitizeUrl($rule));
        HRouter::checkServices();

        if(count($rule) < count(HRouter::$url) && $options['args'] !== true) return false;
        foreach($rule as $x => $text)
        {
            if(HRouter::getFragment($x) === false)
                return false;
            if(in_array($text, HRouter::$reserved))
            {
                if($text === ':arg')
                    $router['args'][] = HRouter::getFragment($x);
                else
                    $router[substr($text, 1)] = HRouter::getFragment($x);
            }
            elseif($text !== HRouter::getFragment($x))
                return false;
        }

        foreach($options as $key => $option)
        {
            if(in_array($key, array('controller', 'action')))
                $router[$key] = $option;
        }

        if($options['args'] === true)
        {
            $x++;
            while(HRouter::getFragment($x) !== false)
            {
                $router['args'][] = HRouter::getFragment($x);
                $x++;
            }
        }

        HRouter::$controller   = $router['controller'];
        HRouter::$action       = $router['action'];
        HRouter::$args         = $router['args'];

        if(!empty($options['system']))
            HRouter::$system   = $options['system'];

        HRouter::$routing = true;
        return true;
    }

    /**
     * get fragment of url
     *
     * @param integer $x
     * @return mixed
     */
    private static function getFragment($x)
    {
        if(isset(HRouter::$url[$x]))
            return HRouter::$url[$x];
        else
            return false;
    }

    /**
     * make the url equal for service url
     *
     * @param void
     * @return boolean
     */
    private static function checkServices()
    {
        if(empty(HRouter::$services))
            return false;

        foreach(HRouter::$services as $service)
        {
            if(HRouter::$url[0] === $service)
            {
                HRouter::$service = $service;
                unset(HRouter::$url[0]);
                foreach(HRouter::$url as $part)
                    $newUrl[] = $part;
                HRouter::$url = $newUrl;
                return true;
            }
        }
        return false;
    }

}