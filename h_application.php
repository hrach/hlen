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

define('CORE', dirname(__FILE__).'/');
define('COMPONENTS', CORE.'components/');
define('APP', dirname($_SERVER['SCRIPT_FILENAME']).'/application/');

require_once CORE.'h_basics.php';

/**
 * __autoload
 * @param string $class
 * @return void
 */
function __autoload($class)
{
    $file = HBasics::underscore($class);
    require_once CORE."$file.php";
}

class HApplication {

    /** @var integer */
    static private $startTime;

    /** @var HController */
    static public $controller;

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

        HRouter::start(APP.'config/router.php');

        try {

            self::loadController();
            self::createController();
            self::callMethod();
            self::$controller->renderView();

        } catch (Exception $e) {
            try {

                self::error( $e->getCode(), $e->getMessage() );
                exit;

            } catch (Exception $er) {
                HDebug::dump($er);
                // TODO
            }
        }

        if(HConfigure::read('Core.debug') > 1)
            echo "<!-- time: ".round((microtime(true)- self::$startTime)*1000, 2)." ms -->";
    }

    /**
     * method caller
     *
     * @return void
     */
    private static function error($code, $message)
    {
        HRouter::$args = array($code, $message, HRouter::$controller, HRouter::$action);
        HRouter::$controller = "system";
        HRouter::$action = "error";

        self::$error = true;
        self::$system = true;

        self::loadController();
        self::createController();
        self::callMethod();
        self::$controller->renderView();
    }

    /**
     * load controller file
     *
     * @param void
     * @return void
     */
    private static function loadController()
    {
        HBasics::load( APP.'/controllers/controller.php' );

        if (!class_exists('Controller', false)) {
            eval("class Controller extends HController {}");
        }

        if ( HBasics::load(APP."controllers/".HRouter::$controller."_controller.php") ||
             ( self::$system &&
               HBasics::load(CORE."controllers/".HRouter::$controller."_controller.php"))
        ) {
            return true;
        }

        throw new RuntimeException(HRouter::$controller, 1001);
    }

    /**
     * create controller's object
     *
     * @param void
     * @return void
     */
    private static function createController()
    {
        $controller = HBasics::camelize( HRouter::$controller ) ."Controller";

        if (!class_exists( $controller, false )) {
            throw new RuntimeException(HRouter::$controller, 1001);
        } else {
            self::$controller = new $controller;
        }
    }

    /**
     * method caller
     *
     * @param void
     * @return void
     */
    private static function callMethod()
    {
        self::$controller->view = HRouter::$action;

        if (!method_exists( self::$controller, HRouter::$action ))  {
            throw new BadMethodCallException(HRouter::$action, 1002);
        } else {
            call_user_func_array( array(self::$controller, HRouter::$action), HRouter::$args );
        }
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