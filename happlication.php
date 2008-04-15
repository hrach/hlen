<?php

/**
 * HLEN FRAMEWORK
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5 $WCREV$
 * @package     Hlen
 */

HApplication::$startTime = microtime(true);

define('CORE', dirname(__FILE__) . '/');

if (!defined('APP')) {
    define('APP', dirname($_SERVER['SCRIPT_FILENAME']) . '/app/');
}

require_once dirname(__FILE__) . '/hconfigure.php';
require_once dirname(__FILE__) . '/hdebug.php';
require_once dirname(__FILE__) . '/hautoload.php';
require_once dirname(__FILE__) . '/hbasics.php';
require_once dirname(__FILE__) . '/hrouter.php';
require_once dirname(__FILE__) . '/hhttp.php';

HConfigure::loadYaml(APP . 'config.yaml');


/**
 * Trida HApplication ma na starost cely chod aplikace
 */
class HApplication
{

    static public $controller;
    static public $startTime;
    static public $admin;
    static public $error = false;

    /**
     * Spustí celou aplikaci:<br />
     * Pokud je aktivni debug mod, zapne odchycení chyb.<br />
     * Po provedení pozadavku vypise debug informace.
     *
     * @return  void
     */
    public static function run()
    {
        HAutoload::registerAutoload();

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

        if (self::$controller->view->getLayout() != 'layout_xml') {
            if ($debug > 1 && class_exists('HDb', false)) {
                echo HDb::getDebug();
            }

            if ($debug > 0) {
                echo "\n<!-- time: " . round((microtime(true) - self::$startTime) * 1000, 2) . ' ms -->';
            }
        }
    }

    /**
     * Zobrazi chybovou chybovou zpravu.<br />
     * Pokud je ladici rezim vypnut, zobrazi se chyba 404.
     *
     * @param   string  jmeno view
     * @param   bool    nahradit v non-debug 404
     * @return  void
     */
    public static function error($view, $debug = false)
    {
        self::$error = true;

        if ($debug === true && HConfigure::read('Core.debug', 0) === 0) {
            HHttp::headerError('404');
            self::$controller->view->view('404');
        } else {
            self::$controller->view->view($view);
        }
    }

    /**
     * Vytvori controller; vola prislusne chybove metody
     *
     * @param   string  jmeno controlleru
     * @return  void
     */
    private static function createController($controllerName)
    {
        if (!class_exists('Controller')) {
            eval('class Controller extends HController {}');
        }

        $namespace = null;
        if (HRouter::$namespace !== false) {
            $namespace = HBasics::camelize(HRouter::$namespace);
        }

        $controllerClass = $namespace . HBasics::camelize($controllerName) . 'Controller';

        if ($controllerClass === 'Controller') {
            self::$controller = new Controller;
            self::error('routing', true);
        } elseif (!class_exists($controllerClass)) {
            self::$controller = new Controller;
            self::error('controller', true);
            self::$controller->view->missingController = $controllerClass;
        } else {
            self::$controller = new $controllerClass;
        }
    }

}