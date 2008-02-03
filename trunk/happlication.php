<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.3
 * @package    Hlen
 */

HApplication::$startTime = microtime(true);

define('CORE', dirname(__FILE__) . '/');

if (!defined('APP')) {
    define('APP', dirname($_SERVER['SCRIPT_FILENAME']) . '/app/');
}

class HApplication
{

    static public $controller;
    static public $error = false;
    static public $system = false;
    static public $startTime;

    public static function run()
    {
        HRouter::route();
        set_exception_handler(array('HApplication', 'exception'));

        self::createController(HRouter::$controller);
        self::render();

        if (HConfigure::read('Core.debug') > 2 && class_exists('HDb', false)) {
            echo HDb::getDebug();
        }

        if (HConfigure::read('Core.debug') > 1) {
            echo '<!-- time: ' . round((microtime(true) - self::$startTime) * 1000, 2) . ' ms -->';
        }
    }

    public static function exception($exception)
    {
        self::$controller = new Controller;
        self::error('sql');
        self::$error = true;
        self::$controller->set('exception', $exception);
        self::$controller->renderPage();
    }

    public static function error($view)
    {
        self::$controller->view->__missingView__ = self::$controller->viewPath;
        
        self::$error = true;
        self::$system = true;

        if (HConfigure::read('Core.debug') > 1) {
            self::$controller->view->view($view);
        } else {
            self::$controller->view->view('404');
        }
    }

    public static function systemUrl($url)
    {
        if ($url[0] !== '/') {
            $url = HRouter::$controller . '/' . $url;
        }

        return HHttp::sanitizeUrl($url);
    }

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

    private static function render()
    {
        self::$controller->render();
    }

}