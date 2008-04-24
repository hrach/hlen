<?php

/**
 * HLEN FRAMEWORK
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5 $WCREV$
 * @package     Hlen
 */

require_once dirname(__FILE__) . '/hview.php';


/**
 * Trida HController
 */
class HController
{

    private $catchedArgs = array();

    /**
     * Konstruktor
     *
     * @param   string  jmeno view tridy
     * @return  void
     */
    public function __construct($viewClass = 'HView')
    {
        $this->view = new $viewClass();

        $this->view->controller = $this;
        $this->view->baseUrl    = HHttp::getInternalUrl();
        $this->view->realUrl    = HHttp::getRealUrl();
        $this->view->escape     = 'htmlspecialchars';
        $this->view->title      = 'HLEN framework';
    }

    /**
     * Presmeruje na novou url
     *
     * @param   string  url - relativni
     * @param   bool    zavolat po presmerovani exit
     * @param   bool    predana absolutni interni url
     * @return  void
     */
    public function redirect($url, $exit = true, $absoluteiUrl = true)
    {
        if (!$absoluteiUrl) {
            $url = HHttp::getInternalUrl() . $url;
        }
        HHttp::headerRedirect(HHttp::getServerUrl() . $url);

        if ($exit) {
            exit;
        }
    }

    /**
     * Zachyti argument pro jeho automaticke pouziti v url
     *
     * @param   string  jmeno argumentu
     * @return  bool
     */
    public function catchArg($name)
    {
        if (is_string($name) && isset(HRouter::$args[$name])) {
            $this->catchedArgs[$name] = HRouter::$args[$name];
            return true;
        }
        return false;
    }

    /**
     * Vrati pole/url-retezec argumentu
     *
     * @param   array           pole novych argumetnu
     * @param   bool            dedit argumenty
     * @param   bool            vratit pole
     * @return  string|array
     */
    public function urlArgs(array $args = array(), $inherited = true, $returnArray = false)
    {
        if ($inherited) {
            $args = array_merge($this->catchedArgs, $args);
        }

        foreach ($args as $name => $value) {
            if ($value === false) {
                unset($args[$name]);
            } elseif (is_string($name) && !isset(HRouter::$replaceNamedArgs[$name])) {
                $args[$name] = $name . ':' . $value;
            }
        }

        if ($returnArray) {
            return $args;
        } else {
            return implode('/', $args);
        }
    }

    /**
     * Vytvori URL v ramci frameworku
     *
     * @param   string|array    1) string - 'controller|action|service'
     *                          2) array - array('controller' => '', 'action' => '', 'service' => '')
     * @param   array           argumenty
     * @param   bool            zdedit argumenty (dedi se pouze zachycene jmenne argumenty!)
     * @param   string          pravidlo, podle ktereho se ma url vytvorit
     * @param   bool            absolutni url
     * @return  string
     */
    public function url($driver = null, array $args = array(), $inheritedArgs = true, $rule = null, $absoluteUrl = false)
    {
        $newUrl = array();

        if ($rule === null) {
            $rule = HRouter::$rule;
        } else {
            $rule = HHttp::urlToArray($rule);
        }

        if (is_array($driver)) {
            $controller = isset($driver['controller']) ? $driver['controller'] : null;
            $action     = isset($driver['action'])     ? $driver['action']     : null;
            $service    = isset($driver['service'])    ? $driver['service']    : null;
        } else {
            $driver     = explode('|', $driver);
            $controller = isset($driver[0]) ? $driver[0] : null;
            $action     = isset($driver[1]) ? $driver[1] : null;
            $service    = isset($driver[2]) ? $driver[2] : null;
        }

        $args = $this->urlArgs($args, $inheritedArgs, true);

        foreach ($rule as $index => $value) {
            switch ($value) {
                case ':controller':
                    if (!empty($controller)) {
                        $newUrl[$index] = $controller;
                    } else {
                        $newUrl[$index] = HRouter::$controller;
                    }
                    break;
                case ':action':
                    if (!empty($action)) {
                        $newUrl[$index] = $action;
                    } else {
                        $newUrl[$index] = HRouter::$action;
                    }
                    break;
                case ':arg':
                    $newUrl[$index] = array_shift($args);
                    break;
                default:
                    $newUrl[$index] = $value;
                    break;
            }
        }

        while (is_array($args) && !empty($args)) {
            $newUrl[] = array_shift($args);
        }

        if (!empty($service)) {
            $newUrl[] = $service;
        }

        $newUrl = HHttp::getInternalUrl() . implode('/', $newUrl);

        if ($absoluteUrl) {
            $newUrl = HHttp::getServerUrl() . $newUrl;
        }

        return $newUrl;
    }

    /**
     * Vrati hodnotu jmenneho argumentu
     *
     * @param   string  jmeno argumentu
     * @param   mixed   defaultni hodnota, pokud argument neexistuje; nastaveno na false
     * @return  mixed
     */
    public function getArg($name, $default = false)
    {
        if (isset(HRouter::$args[$name])) {
            return HRouter::$args[$name];
        } else {
            return $default;
        }
    }

    /**
     * Spusti volani action a rendering
     *
     * @return  void
     */
    public function render()
    {
        static $run = false;

        if ($run === false) {
            $run = true;
            $methodName = HRouter::$action . 'Action';
            $methodExists = method_exists(get_class($this), $methodName);

            if (!$methodExists) {
                if (!HApplication::$error) {
                    HApplication::error('method', true);
                    HApplication::$controller->view->missingMethod = $methodName;
                    $methodName = false;
                }
            } else {
                $this->view->view(HRouter::$action);
            }

            if (method_exists($this, 'init')) {
                call_user_func(array($this, 'init'));
            }

            if ($methodName !== false && $methodExists) {
                call_user_func_array(array($this, $methodName), HRouter::$args);
            }

            $this->view->render();
        }
    }

}