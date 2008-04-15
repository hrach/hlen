<?php

/**
 * HLEN FRAMEWORK
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5 $WCREV$
 * @package     Hlen
 */

require_once dirname(__FILE__) . '/hhttp.php';


/**
 * Trida HRouter routuje celou vasi aplikace
 */
class HRouter
{

    public static $segments = array();
    public static $allowedNamedArgs = array();
    public static $replaceNamedArgs = array();

    public static $controller;
    public static $action;
    public static $args = array();
    public static $rule = array();
    public static $namespace = false;

    public static $service = false;
    public static $system = false;

    private static $routing = false;


    /**
     * Parsuje url a ulozi do $segements
     */
    public static function staticConstruct()
    {
        self::$segments = HHttp::urlToArray(HHttp::getRequestUrl());
    }

    /**
     * Prida sluzbu - renderovani alternativniho obsahu
     *
     * @param   string  jmeno sluzby
     * @return  void
     */
    public static function addService($service)
    {
        $lastKey = count(self::$segments) - 1;

        if (self::$service === false && isset(self::$segments[$lastKey])
                                     && self::$segments[$lastKey] === $service) {
            self::$service = $service;
            array_pop(self::$segments);
        }
    }

    /**
     * Interne prepise url pri shode s $rule na $newUrl
     * Vhode pro zpetnou kompabilitu 
     *
     * @param   string  url, pri kterem prepsat
     * @param   string  nove url
     * @return  bool
     */
    public static function rewrite($rule, $newUrl)
    {
        $url  = HHttp::sanitizeUrl(HHttp::getRequestUrl());
        $rule = HHttp::sanitizeUrl($rule);

        if ($url === $rule) {
            HHttp::headerRedirect(HHttp::getServerUrl() . HHttp::getInternalUrl() . $newUrl);
            exit;
        }

        return false;
    }

    /**
     * Pripoji se k Url
     *
     * @param   string  url vyraz
     * @param   array   nastaveni
     * @param   array   prirazeni jmen argumentum, pravdilo klic a hodnota:
     *                  $poziceVurl => $jmenoArgumetnu
     * @return  bool
     */
    public static function connect($rule, array $options = array(), array $namedArg = array())
    {
        static $ruleModificators = array(':controller', ':action', ':arg');

        if (self::$routing) {
            return false;
        }

        $router['controller']  = '';
        $router['action']      = 'index';
        $router['args']        = array();

        $rule          = HHttp::urlToArray($rule);
        $segmentCount  = count(self::$segments);
        $lastRuleKey   = count($rule) - 1;
        $multiArgs     = false;

        if (isset($rule[$lastRuleKey]) && $rule[$lastRuleKey] == '*') {
            array_pop($rule);
            $multiArgs = true;
        }

        $ruleCount = count($rule);

        if (($ruleCount > $segmentCount) ||
            ($ruleCount < $segmentCount && $multiArgs === false) ||
            ($ruleCount == 0 && $multiArgs === true)) {
                return false;
        }

        $key = -1;
        foreach ($rule as $key => $val) {
            if ($val === ':arg') {
                $arg = self::sanitizeNamedArg(self::getSegment($key));

                if (isset($namedArg[$key])) {
                    $arg = array($namedArg[$key], $arg[1]);
                    self::$replaceNamedArgs[$namedArg[$key]] = true;
                }

                if ($arg[0] === -1) {
                    $router['args'][] = $arg[1];
                } else {
                    $router['args'][$arg[0]] = $arg[1];
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

        if (isset($options['rule']) && !empty($options['rule'])) {
            $router['rule'] = HHttp::urlToArray($options['rule']);
        } else {
            $router['rule'] = $rule;
        }

        if ($multiArgs) {
            while (self::getSegment(++$key) !== false) {
                $arg = self::sanitizeNamedArg(self::getSegment($key));

                if (isset($namedArg[$key])) {
                    $arg = array($namedArg[$key], $arg[1]);
                    self::$replaceNamedArgs[$namedArg[$key]] = true;
                }

                if ($arg[0] === -1) {
                    $router['args'][] = $arg[1];
                } else {
                    $router['args'][$arg[0]] = $arg[1];
                }
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

        if (isset($options['namespace']) && !empty($options['namespace'])) {
            self::$namespace = $options['namespace'];
        }

        self::$controller = $router['controller'];
        self::$action     = strtolower($router['action']);
        self::$args       = $router['args'];
        self::$rule       = $router['rule'];
        self::$routing    = true;

        return true;
    }

    /**
     * Vrati segment z url
     *
     * @param   int     cislo segmentu
     * @return  mixed   pokud segment neexistuje, vraci metoda false
     */
    public static function getSegment($x)
    {
        if (isset(self::$segments[$x])) {
            return self::$segments[$x];
        }

        return false;
    }

    /**
     * Argument je preveden na pole
     * Pokud je argument jmenny pak je jeho klic vracen misto zvlast a odstranen z hodnoty
     *
     * @param   string  argument
     * @return  array
     */
    private static function sanitizeNamedArg($arg)
    {
        foreach (self::$allowedNamedArgs as $name) {
            $len = strlen($name) + 1;
            if ($name . ':' === substr($arg, 0, $len)) {
                return array($name, substr($arg, $len));
            }
        }
        
        return array(-1, $arg);
    }

}

HRouter::staticConstruct();