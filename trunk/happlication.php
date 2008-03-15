<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */

HApplication::$startTime = microtime(true);

define('CORE', dirname(__FILE__) . '/');

if (!defined('APP')) {
    define('APP', dirname($_SERVER['SCRIPT_FILENAME']) . '/app/');
}

require_once dirname(__FILE__) . '/hconfigure.php';
require_once dirname(__FILE__) . '/hdebug.php';
require_once dirname(__FILE__) . '/hautoload.php';
require_once dirname(__FILE__) . '/hrouter.php';
require_once dirname(__FILE__) . '/hhttp.php';


class HApplication
{

    static public $controller;
    static public $startTime;
    static public $error = false;

	/*
	 * Spustí celou aplikaci
	 * 
	 * @return	void
	 */
    public static function run()
    {
        HAutoload::registerAutoload();
        HRouter::route();

        $debug = HConfigure::read('Core.debug', 0);
        
        if ($debug > 0) {
        	HDebug::enableErrors();
        	HDebug::enableExceptions(true);
        } else {
        	HDebug::logErrors();
        	HDebug::enableExceptions();
        }

        self::createController(HRouter::$controller);
        self::$controller->render();

        if ($debug > 1 && class_exists('HDb', false)) {
            echo HDb::getDebug();
        }

        if ($debug > 0) {
            echo "\n<!-- time: " . round((microtime(true) - self::$startTime) * 1000, 2) . ' ms -->';
        }
    }

    /*
     * Zobrazi chybovou chybovou zpravu
     * Pokud je ladici rezim vypnut, zobrazi se chyba 404
     * 
     * @param	string	jmeno view
     * @return	void
     */
    public static function error($view)
    {
        self::$error  = true;

        if (HConfigure::read('Core.debug', 0) > 0) {
            self::$controller->view->view($view);
        } else {
            HHttp::headerError('404');
  	        self::$controller->view->view('404');
        }
    }

    /*
     * Vytvori controller; vola prislusne chybove metody
     * 
     * @param	string	jmeno controlleru
     * @return	void
     */
    private static function createController($controllerName)
    {
        if (!class_exists('Controller')) {
            eval('class Controller extends HController {}');
        }

        $controllerClass = HBasics::camelize($controllerName) . 'Controller';

        if ($controllerClass === 'Controller') {
            self::$controller = new Controller;
            self::error('routing');
        } elseif (!class_exists($controllerClass)) {
            self::$controller = new Controller;
            self::error('controller');
        } else {
            self::$controller = new $controllerClass;
        }
    }

}