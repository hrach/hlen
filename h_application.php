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

$app_classes = HLoader::getClasses( APP.'app_classes.cache', APP );
$core_classes = HLoader::getClasses( APP.'core_classes.cache', CORE );


function __autoload($class)
{
    global $app_classes, $core_classes;

    if (in_array($class, array_keys($app_classes)))
    {
        $file = $app_classes[$class];
        require_once $file;
    }
    elseif ( in_array($class, array_keys($core_classes))
             && (HApplication::$system
                 || substr(HBasics::underscore($class), 0, 2) === 'h_'))
    {
        $file = $core_classes[$class];
        require_once $file;
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
     * @param void
     * @return void
     */
    public static function run()
    {
        self::$startTime = microtime(true);

        HBasics::load(APP.'config/bootstrap.php');
        HBasics::load(APP.'config/core.php');

        HRouter::start( HHttp::getGet('url'), APP.'config/router.php' );

        try
        {
            self::createController(HRouter::$controller);
            self::callMethod(HRouter::$action, HRouter::$args);
            self::$controller->renderView();
        }
        catch (Exception $e)
        {
            try
            {
                self::$error = true;
                self::$system = true;

                self::createController("system");
                self::callMethod("error", array($e));

                self::$controller->renderView();
                exit;

            } catch (Exception $ex) {
                HDebug::dump($ex);
                // TODO
            }
        }

        if(HConfigure::read('Core.debug') > 1)
            echo "<!-- time: ".round((microtime(true)- self::$startTime)*1000, 2)." ms -->";
    }

    /**
     * create controller's object
     *
     * @param string $controllerName
     * @return void
     */
    private static function createController($controllerName)
    {
        if (!class_exists('Controller')) {
            eval('class Controller extends HController {}');
        }

        $controllerClass = HBasics::camelize($controllerName)."Controller";
        if ($controllerClass === 'Controller' || !class_exists($controllerClass)) {
            throw new Exception("The controller doesn't exists.", 1001);
        }

        self::$controller = new $controllerClass;
    }

    /**
     * method caller
     *
     * @param string $action
     * @param array $args
     * @return void
     */
    private static function callMethod($action, $args)
    {
        self::$controller->view = $action;
        if (!is_callable( array(self::$controller, $action) )) {
            throw new Exception("The method od controller doesn't exists.", 1002);
        }

        call_user_func_array(array(self::$controller, $action), $args);
    }

    /**
     * make system url
     *
     * @param string $url
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