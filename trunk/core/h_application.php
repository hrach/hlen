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


final class HApplication {

    /** @var int */
    private static $startTime;

    /** @var HController */
    public static $controller;

    /** @var boolean */
    public static $error = false;

    /**
     * run the application
     *
     * @param void
     * @return void
     */
    public static function run()
    {
        HApplication::$startTime = microtime(true);

        load(APP.'config/bootstrap.php');
        load(APP.'config/core.php');

        HRouter::start();

        try {

            HApplication::loadController();
            HApplication::createController();
            HApplication::callMethod();
            HApplication::$controller->renderView();

        } catch (DibiException $e) {

            echo "SQL: <br/>";
            HDebug::dump($e);

        } catch (Exception $e) {

            try {
                HApplication::error($e->getCode(), $e->getMessage());
                exit;
            } catch (Exception $er) {
                HDebug::dump($er);
            }

        }

        if(HConfigure::read('Core.debug') > 1)
            echo "<!-- time: ".round((microtime(true)- HApplication::$startTime)*1000, 2)." ms -->";
    }

    private static function error($code, $message)
    {
        HRouter::$args = array($code, $message, HRouter::$controller, HRouter::$action);
        HRouter::$controller = "system";
        HRouter::$action = "error";
        HRouter::$system = true;
        HApplication::$error = true;

        HApplication::loadController();
        HApplication::createController();
        HApplication::callMethod();
        HApplication::$controller->renderView();
    }

    private static function loadController()
    {
        load(APP.'/controllers/controller.php');
        if(!class_exists('Controller', false))
            eval("class Controller extends HController {}");

        if( load(APP."controllers/".HRouter::$controller."_controller.php") ||
            ( HRouter::$system &&
              load(HLEN_CORE."controllers/".HRouter::$controller."_controller.php")
            )
          )
            return true;

        throw new RuntimeException(HRouter::$controller, 1001);
    }

    private static function createController()
    {
        $controller = camelize(HRouter::$controller) ."Controller";
        
        if(!class_exists($controller, false))
            throw new RuntimeException(HRouter::$controller, 1001);
        else
            HApplication::$controller = new $controller;
    }

    private static function callMethod()
    {
        HApplication::$controller->view = HRouter::$action;
        if(!method_exists(HApplication::$controller, HRouter::$action))
            throw new BadMethodCallException(HRouter::$action, 1002);
        else
            call_user_func_array( array(HApplication::$controller, HRouter::$action), HRouter::$args );
    }
    

    /**
     * make system url
     * @param string
     * @return string
     */
    public static function makeSystemUrl($string)
    {
        if($string[0] !== '/')
            $string = HRouter::$controller.'/'.$string;
        return HHttp::sanitizeUrl($string);
    }

}