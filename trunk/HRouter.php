<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/HHttp.php';


/**
 * Parser URL
 *
 * Trida parsuje url pro MVC vrstvenou aplikaci
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HRouter
{

    /** @var string */
    public static $url;
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


    /** @var boolean */
    private static $routing = false;
    /** @var array */
    private static $parseUrl = array();
    /** @var array */
    private static $reserved = array(':controller', ':action', ':arg');
    /** @var array */
    private static $services = array();


    /**
     * Spusti routing
     *
     * @param string $url
     * @param string $router - function name / file name
     */
    public static function start($url, $router)
    {
        self::$url = HHttp::urlToArray($url);

        if (is_callable($router)) {
            call_user_func($router);
        } else {
            HBasics::load($router);
        }

        if (self::$allowDefault) {
            self::connect('/:controller/:action', array('args' => true));
            self::connect('/:controller');
        }
    }

    /**
     * Rezervuje prefix servisu
     *
     * @param mixed $services
     */
    public static function mapService($services)
    {
        self::$services = array_merge(self::$services, (array) $services);
    }

    /**
     * Prepise dane url jinym
     *
     * Vhodne pro zajisteni zpetne kompatibiity
     * @deprecated vyuzijte self::connect
     * @param string $rule
     * @param string $newUrl
     * @return boolean
     */
    public static function rewrite($rule, $newUrl)
    {
        $url = HHttp::sanitizeUrl(HHttp::getGet('url'));
        $rule = HHttp::sanitizeUrl($rule);

        if ($url === $rule) {
            self::$url = HHttp::urlToArray($newUrl);
            return true;
        }
        return false;
    }

    /**
     * Routovani pravidla
     *
     * @param string  $rule
     * @param array   $options = array()
     * @return boolean
     */
    public static function connect($rule, $options = array())
    {
        if (self::$routing) {
            return false;
        }

        $router['action'] = 'index';
        $rule = HHttp::urlToArray($rule);
        self::checkServices();

        if (count($rule) < count(self::$url) && $options['args'] !== true) {
            return false;
        }

        foreach ($rule as $x => $text) {

            if (self::getFragment($x) === false) {
                return false;
            }

            if (in_array($text, self::$reserved)) {
                if ($text === ':arg') {
                    $router['args'][] = self::getFragment($x);
                } else {
                    $router[substr($text, 1)] = self::getFragment($x);
                }
            } elseif ($text !== self::getFragment($x)) {
                return false;
            }
        }

        foreach ($options as $key => $option) {
            if (in_array($key, array('controller', 'action'))) {
                $router[$key] = $option;
            }
        }

        if ($options['args']) {
            while (self::getFragment(++$x) !== false) {
                $router['args'][] = self::getFragment($x);
            }
        }

        self::$controller = $router['controller'];
        self::$action = $router['action'];
        self::$args = $router['args'];

        self::$routing = true;
        return true;
    }

    /**
     * Vrati fragment url
     *
     * @param integer $x
     * @return mixed
     */
    private static function getFragment($x)
    {
        if (isset(self::$url[$x])) {
            return self::$url[$x];
        }
        return false;
    }

    /**
     * Odstrani fragment servisu
     *
     * @return boolean
     */
    private static function checkServices()
    {
        if (empty(self::$services)) {
            return false;
        }

        foreach (self::$services as $service) {
            if (self::$url[0] === $service) {
                self::$service = $service;
                array_shift(self::$url);
                return true;
            }
        }
        return false;
    }

}