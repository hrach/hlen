<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/hhttp.php';


class HRouter
{

    public static $routing = false;
    public static $url;
    public static $segment = array();
    public static $prefix = array();

    public static $base = ':controller/:action';
    public static $rule = array();
    public static $defaultController = '';
    public static $defaultAction = 'index';
    
    public static $controller;
    public static $action;
    public static $args = array();
    public static $multiArgs = true;

    public static $naSeparator = ':';
    public static $system = false;
    public static $service;

    private static $services = array();


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
            self::connect(self::$base, array(
                'multiArgs' => true
            ));
            
            if (self::$base == ':controller/:action') {
                self::connect(':controller');
                self::$rule = array(':controller', ':action');
            }
        }

        if (!self::$routing) {
            self::$controller = self::$defaultController;
            self::$action = self::$defaultAction;
            self::$rule = HHttp::urlToArray(self::$base);
        }
    }

    public static function addService($services)
    {
        self::$services = array_merge(self::$services, (array) $services);
    }

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
        
        if ((count($rule) === 0 && (count($rule) < count(self::$segment) && $options['multiArgs'] === false))
         || (count($rule) > count(self::$segment))) {
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
            foreach ((array) $options['args'] as $argName => $arg) {
                if (!is_integer($argName)) {
                    $newArg = $argName . HRouter::$naSeparator . $arg;
                    $newArg = self::removePrefix($newArg);
                    $router['args'][$argName] = $newArg[1];
                } else {
                    $router['args'][] = $arg;
                }
            }
        }

        if (isset($options['rule'])) {
            $rule = HHttp::urlToArray($options['rule']);
        }

        self::$controller = $router['controller'];
        self::$action     = $router['action'];
        self::$args       = $router['args'];
        
        self::$routing    = true;
        self::$rule       = $rule;
        self::$multiArgs  = $options['multiArgs'];
        return true;
    }

    public static function getSegment($x)
    {
        if (isset(self::$segment[$x])) {
            return self::$segment[$x];
        }

        return false;
    }

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