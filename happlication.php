<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


require_once dirname(__FILE__) . '/hloader.php';

define('CORE', dirname(__FILE__) . '/');
define('APP', dirname($_SERVER['SCRIPT_FILENAME']) . '/app/');

$appClasses = HLoader::getClasses(APP . 'controllers/', APP . 'cache/classes.cache');
$coreFiles = array(
    'hbasics', 'hconfigure', 'hdb',
    'hdebug', 'hform', 'hhtml', 'hcontroller',
    'hhttp', 'hloader', 'hrouter', 'hsession'
);

function __autoload($class)
{
    global $appClasses, $coreFiles;

    if (array_key_exists($class, $appClasses)) {
        require_once $appClasses[$class];
    } elseif(in_array(strtolower($class), $coreFiles)) {
        require_once CORE . strtolower($class) . '.php';
    }
}


class HApplication
{

    static public $controller;
    static public $loader;
    static public $error = false;
    static public $system = false;

    static private $controllers = array();
    static private $startTime;


    public static function run()
    {
        self::$startTime = microtime(true);
        set_exception_handler(array('HApplication', 'exception'));

        HBasics::load(APP.'config/bootstrap.php');
        HBasics::load(APP.'config/core.php');

        HRouter::start(HHttp::getGet('url'), APP.'config/router.php');

        self::createController(HRouter::$controller);
        self::render();

        if (class_exists('HConfigure', false) && HConfigure::read('Core.debug') > 1) {
            if (class_exists('HDb', false)) {
                echo HDb::getDebug();
            }
            echo '<!-- time: ' . round((microtime(true)- self::$startTime)*1000, 2) . ' ms -->';
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
        self::$controller->set('__missingView__', self::$controller->viewPath);
        
        self::$error = true;
        self::$system = true;

        if (HConfigure::read('Core.debug') > 1) {
            self::$controller->view = $view;
        } else {
            self::$controller->view = '404';
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