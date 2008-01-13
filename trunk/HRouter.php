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
 * @version   0.2.2
 */
class HRouter
{

    /** @var boolean */
    public static $routing = false;
    /** @var string */
    public static $url;
    /** @var array */
    public static $segment = array();
    /** @var array */
    public static $prefix = array();


    /** @var string */
    public static $base = ':controller/:action';
    /** @var array */
    public static $rule = array();
    /** @var string */
    public static $defaultController = '';
    /** @var string */
    public static $defaultAction = 'index';
    


    /** @var string */
    public static $controller;
    /** @var string */
    public static $action;
    /** @var array */
    public static $args = array();


    /** @var boolean */
    public static $multiArgs = true;
    /** @var string */
    public static $naSeparator = ':';


    /** @var boolean */
    public static $system = false;
    /** @var string */
    public static $service;
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
        self::$segment = HHttp::urlToArray($url);
       
        if (is_callable($router)) {
            call_user_func($router);
        } else {
            if (file_exists($router)) {
                include $router;
            }
        }

        if (!self::$routing) {
            self::connect(self::$base, array('multiArgs' => true));
        }

        if (!self::$routing) {
            self::$controller = self::$defaultController;
            self::$action = self::$defaultAction;
            self::$rule = HHttp::urlToArray(self::$base);
        }
    }

    /**
     * Rezervuje posledni segment pro servis
     *
     * @param mixed $services
     */
    public static function addService($services)
    {
        self::$services = array_merge(self::$services, (array) $services);
    }

    /**
     * Prepise dane url jinym
     *
     * Vhodne pro zajisteni zpetne kompatibiity
     * @param string $rule
     * @param string $newUrl
     * @return boolean
     */
    public static function rewrite($rule, $newUrl)
    {
        $url = HHttp::sanitizeUrl(HHttp::getGet('url'));
        $rule = HHttp::sanitizeUrl($rule);

        if ($url === $rule) {
            self::$segment = HHttp::urlToArray($newUrl);
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
        
        self::removeServicSegment();
        
        if (!isset($options['multiArgs'])) {
            $options['multiArgs'] = false;
        }

        $router['controller'] = self::$defaultController;
        $router['action'] = self::$defaultAction;
        $router['args'] = array();
        
        $rule = HHttp::urlToArray($rule);
        $key = -1;

        // pokud se nerovna pocet segmentu
        // a neni povoleno neomezen mnozstvi argumentu,
        // routing se neprovadi
        if ((count($rule) === 0 && (count($rule) < count(self::$segment) && $options['multiArgs'] === false)) || (count($rule) > count(self::$segment))) {
            return false;
        }
        
        foreach ($rule as $key => $val) {
            if (in_array($val, array(':controller', ':action', ':arg'))) {
                if ($val === ':arg') {
                    $nArg = self::removePrefix(self::getSegment($key));
                    $router['args'][$nArg[0]] = $nArg[1];
                } else {
                    $router[substr($val, 1)] = self::getSegment($key);
                }
            } elseif ($val !== self::getSegment($key)) {
                return false;
            }
        }

        foreach ($options as $att => $option) {
            if (in_array($att, array('controller', 'action'))) {
                $router[$att] = $option;
            }
        }
        
        if ($options['multiArgs']) {
            while (self::getSegment(++$key) !== false) {
                $nArg = self::removePrefix(self::getSegment($key));
                $router['args'][$nArg[0]] = $nArg[1];
            }
        }
        
        if (isset($options['args'])) {
            foreach ((array) $options['args'] as $arg) {
                if (is_array($arg)) {
                    $arg = implode(HRouter::$naSeparator, $arg);
                }   
                $nArg = self::removePrefix($arg);
                if (!isset($router['args'][$nArg[0]])) {
                    $router['args'][$nArg[0]] = $nArg[1];
                }
            }
        }

        self::$controller = $router['controller'];
        self::$action     = $router['action'];
        self::$args       = $router['args'];
        
        self::$routing    = true;
        self::$rule       = $rule;
        self::$multiArgs  = $options['multiArgs'];

        return true;
    }

    /**
     * Vrati fragment url
     *
     * @param integer $x
     * @return mixed
     */
    public static function getSegment($x)
    {
        if (isset(self::$segment[$x])) {
            return self::$segment[$x];
        }

        return false;
    }

    /**
     * Odstrani prefix promenne
     *
     * @param string $arg
     */
    private static function removePrefix($arg)
    {
        static $index = 0;

        foreach (self::$prefix as $prefix) {
            $part = substr($arg, 0, strlen($prefix) + 1);
            if ($prefix . self::$naSeparator === $part) {
                return array($prefix, substr($arg, strlen($prefix) + 1));
            }
        }

        return array($index++, $arg);
    }

    /**
     * Odstrani fragment servisu
     *
     * @return boolean
     */
    private static function removeServicSegment()
    {
        if (empty(self::$services)) {
            return false;
        }

        foreach (self::$services as $service) {
            if (self::$segment[count(self::$segment)-1] === $service) {
                self::$service = $service;
                array_pop(self::$segment);
                return true;
            }
        }

        return false;
    }

}