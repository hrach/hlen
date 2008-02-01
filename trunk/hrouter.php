<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/hhttp.php';


class HRouter
{

    public static $segment = array();
    public static $routing = false;

    public static $defaultController = '';
    public static $defaultAction = 'index';
    public static $defaultRuleKey = 0;

    public static $rules = array(':controller/:action/*');

    public static $namedArguments = array();
    public static $namedArgumentsSeparator = ':';

    public static $controller;
    public static $action;
    public static $args = array();
    public static $rule = array();

    public static $service;
    public static $system = false;

    private static $services = array();


    public static function start($url, $router, $isFile = true)
    {
        self::$segment = HHttp::urlToArray($url);

        if (!$isFile) {
            call_user_func($router);
        } else {
            include $router;
        }

        if (!self::$routing) {
            self::connect(self::$rules[0]);

            if (self::$rules[0] == ':controller/:action/*') {
                self::connect(':controller', array('ruleKey' => 0));
            }
        }
    }

    public static function addService($service)
    {
        self::$services[] = $service;

        if (self::$segment[count(self::$segment)-1] === $service) {
            self::$service = $service;
            array_pop(self::$segment);
        }
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

    public static function connect($rule, array $options = array(), array $restrictions = array())
    {
        static $ruleModificators = array(':controller', ':action', ':arg');

        if (self::$routing) {
            return false;
        }

        $router['controller']  = self::$defaultController;
        $router['action']      = self::$defaultAction;
        $router['args']        = array();

        $rule          = HHttp::urlToArray($rule);
        $segmentCount  = count(self::$segment);

        if ($rule[count($rule) - 1] === '*') {
            array_pop($rule);
            $multiArgs = true;
        } else {
            $multiArgs = false;
        }

        $ruleCount = count($rule);

        if (($ruleCount === 0 && $multiArgs === true) || ($ruleCount < $segmentCount && $multiArgs === false) || $ruleCount > $segmentCount) {
            return false;
        }

        $key = -1;
        foreach ($rule as $key => $val) {
            if ($val === ':arg') {
                $newArg = self::removePrefix(self::getSegment($key));
                if (is_integer($newArg[0])) {
                    $router['args'][] = $nArg[1];
                } else {
                    $router['args'][$nArg[0]] = $nArg[1];
                }
            } elseif ($val === ':controller') {
                $router['controller'] = self::getSegment($key);
            } elseif ($val === ':action') {
                $router['action'] = self::getSegment($key);
            } elseif ($val !== self::getSegment($key)) {
                return false;
            }
        }

        if (isset($options['controller'])) {
            $router['controller'] = $options['controller'];
        }

        if (isset($options['action'])) {
            $router['action'] = $options['action'];
        }

        if (isset($options['ruleKey']) && isset(self::$rules[$options['ruleKey']])) {
            $router['rule'] = HHttp::urlToArray(self::$rules[$options['ruleKey']]);
        } else {
            $router['rule'] = $rule;
        }

        if ($multiArgs) {
            while (self::getSegment(++$key) !== false) {
                $nArg = self::removePrefix(self::getSegment($key));
                $router['args'][$nArg[0]] = $nArg[1];
            }
        }

        if (isset($options['args'])) {
            foreach ((array) $options['args'] as $argName => $arg) {
                if (!is_integer($argName)) {
                    $router['args'][$argName] = $arg;
                } else {
                    $router['args'][] = $arg;
                }
            }
        }

        self::$controller = $router['controller'];
        self::$action     = $router['action'];
        self::$args       = $router['args'];
        self::$rule       = $router['rule'];

        self::$routing    = true;
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
        foreach (self::$namedArguments as $prefix) {
            $part = substr($arg, 0, strlen($prefix) + 1);
            if ($prefix . self::$namedArgumentsSeparator === $part) {
                return array($prefix, substr($arg, strlen($prefix) + 1));
            }
        }
        return array(0, $arg);
    }

}