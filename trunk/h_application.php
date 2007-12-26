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

require_once dirname(__FILE__).'/h_basics.php';
require_once dirname(__FILE__).'/h_loader.php';

define('CORE',          dirname(__FILE__).'/');
define('COMPONENTS',    CORE.'components/');
define('APP',           dirname($_SERVER['SCRIPT_FILENAME']).'/application/');

$app_classes = HLoader::getClasses( APP, APP.'classes.cache' );

function __autoload($class)
{
    global $app_classes;

    $underScoreClassName = HBasics::underscore($class);

    if (in_array($class, array_keys($app_classes))) {
        require_once $app_classes[$class];
    } elseif (substr($underScoreClassName, 0, 2) === 'h_') {
        require_once CORE . $underScoreClassName . ".php";
    }
}






/* ===================================================================================== */
/*                                  Main Application                                     */
/* ===================================================================================== */






class HApplication {

    /** @var integer */
    static private $startTime;

    /** @var HController */
    static public $controller;

    /** @var HLoader */
    static public $loader;

    /** @var array */
    static private $controllers = array();

    /** @var boolean */
    static public $error = false;
    /** @var boolean */
    static public $system = false;

    /**
     * run the application
     *
     * @param  void
     * @return void
     */
    public static function run()
    {
        self::$startTime = microtime(true);
        set_exception_handler( array('HApplication', 'exception') );

        HBasics::load(APP.'config/bootstrap.php');
        HBasics::load(APP.'config/core.php');

        HRouter::start( HHttp::getGet('url'), APP.'config/router.php' );

        self::createController(HRouter::$controller);
        self::callMethod(HRouter::$action, HRouter::$args);
        self::$controller->renderView();

        if (class_exists('HConfigure', false) && HConfigure::read('Core.debug') > 1) {
            if (class_exists('HDibi', false)) {
                echo HDibi::getDebug();
            }
            echo "<!-- time: ".round((microtime(true)- self::$startTime)*1000, 2)." ms -->";
        }
    }

    /**
     * catcher exceptions
     *
     * @param $exception
     * @return void
     */
    public function exception($exception)
    {
        
        if (substr(get_class($exception), 0, 4) === 'Dibi')
        {
            echo "Dibi: ". $exception->getMessage();
        }
        else
        {
            echo $exception->getMessage();
        }

    }

    /**
     * error
     *
     * @param  string  $view
     * @return void
     */
    public function error($view)
    {
        self::$error = true;
        self::$system = true;

        if (HConfigure::read('Core.debug') > 1) {
            self::$controller->view = $view;
        } else {
            self::$controller->view = '404';
        }
    }

    /**
     * create controller's object
     *
     * @param  string $controllerName
     * @return void
     */
    private static function createController($controllerName)
    {
        if (!class_exists('Controller')) {
            eval('class Controller extends HController {}');
        }

        $controllerClass = HBasics::camelize($controllerName)."Controller";
        if ($controllerClass === 'Controller')
        {
            $controllerClass = "Controller";
            self::$controller = new $controllerClass;
            self::error('routing');
        }
        elseif (!class_exists($controllerClass))
        {
            $controllerClass = "Controller";
            self::$controller = new $controllerClass;
            self::error('controller');
        }
        else
        {
            self::$controller = new $controllerClass;
        }
    }

    /**
     * method caller
     *
     * @param  string  $action  Method of controller
     * @param  array   $args    Arguments for method
     * @return void
     */
    private static function callMethod($action, $args)
    {
        $methodExists = is_callable( array(self::$controller, $action) );
        if (!$methodExists) {
            if(!self::$error) {
                self::error("method");
            }
            return;
        }

        self::$controller->view = $action;

        self::$controller->__callBeforeMethod();
        call_user_func_array(array(self::$controller, $action), $args);
    }

    /**
     * make system url
     *
     * @param  string $url
     * @return string
     */
    public static function systemUrl($url)
    {
        if ($url[0] !== '/') {
            $url = HRouter::$controller.'/'.$url;
        }

        return HHttp::sanitizeUrl($url);
    }

}