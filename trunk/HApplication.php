<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/HLoader.php';

define('CORE', dirname(__FILE__) . '/');
define('APP', dirname($_SERVER['SCRIPT_FILENAME']) . '/app/');

$appClasses = HLoader::getClasses(APP . 'controllers/', APP . 'cache/classes.cache');

/**
 * Nacte soubor prislusny tride $class
 *
 * Soubor je nejprve hledan v cache aplikace
 * pokud neni soubro nalezen a zacina na pismeno "h", povazuje se za soubor frameworku
 * @global array $appClasses
 * @param string $className
 */
function __autoload($class)
{
    global $appClasses;

    if (in_array($class, array_keys($appClasses))) {
        require_once $appClasses[$class];
    } elseif($class[0] === 'H') {
        require_once CORE . $class . ".php";
    }
}


/**
 * Ridici trida MVC aplikace
 *
 * Zkombinuje dohoromady vsechny potrebne tridy Hlenu
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.2.0
 */
class HApplication
{

    /** @var HController */
    static public $controller;
    /** @var HLoader */
    static public $loader;
    /** @var boolean */
    static public $error = false;
    /** @var boolean */
    static public $system = false;

    /** @var array */
    static private $controllers = array();
    /** @var integer */
    static private $startTime;


    /**
     * Spust? celou MVC aplikaci
     */
    public static function run()
    {
        self::$startTime = microtime(true);
        set_exception_handler(array('HApplication', 'exception'));

        HBasics::load(APP.'config/bootstrap.php');
        HBasics::load(APP.'config/core.php');

        HRouter::start(HHttp::getGet('url'), APP.'config/router.php');

        self::createController(HRouter::$controller);
        self::callMethod(HRouter::$action, HRouter::$args);
        self::$controller->renderView();

        if (class_exists('HConfigure', false) && HConfigure::read('Core.debug') > 1) {
            if (class_exists('HDb', false)) {
                echo HDb::getDebug();
            }
            echo '<!-- time: ' . round((microtime(true)- self::$startTime)*1000, 2) . ' ms -->';
        }
    }

    /**
     * Odchyti vyjimky a vypise vystup
     *
     * @todo zpracovat graficky vystup
     * @param $exception Exception
     */
    public static function exception($exception)
    {
        self::$controller = new Controller;
        self::error('sql');
        self::$controller->set('exception', $exception);
        self::$controller->renderView();
    }

    /**
     * Nastav? prislusnou chybovou sablonu
     * Mimo ladici rezim nastavuje automaticky E404
     *
     * @param string $view
     */
    public static function error($view)
    {
        self::$controller->set('__missingView__', self::$controller->view);

        self::$error = true;
        self::$system = true;

        if (HConfigure::read('Core.debug') > 1) {
            self::$controller->view = $view;
        } else {
            self::$controller->view = '404';
        }
    }

    /**
     * Vytvor? systemove url
     *
     * Pokud je treba, prida jmeno controlleru
     * @param string $url
     * @return string
     */
    public static function systemUrl($url)
    {
        if ($url[0] !== '/') {
            $url = HRouter::$controller . '/' . $url;
        }

        return HHttp::sanitizeUrl($url);
    }

    /**
     * Vytvori instanci objektu $controllerName
     *
     * @param string $controllerName
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

    /**
     * Zavola metodu $action s argumenty $args tridy self::$controller
     *
     * @param string  $action
     * @param array   $args
     */
    private static function callMethod($action, $args)
    {
        if (method_exists(self::$controller, 'init')) {
            call_user_func(array(self::$controller, 'init'));
        }

        $actionName = $action . 'Action';
        $methodExists = method_exists(self::$controller, $actionName);

        if (!$methodExists) {
            if (!self::$error) {
                self::error('method');
            }
            return;
        } else {
            self::$controller->view = $action;
        }

        call_user_func_array(array(self::$controller, $actionName), $args);
    }

}