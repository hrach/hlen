<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.5
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/hview.php';


class HController
{

    private $catchedArgs = array();

    /*
     * Konstruktor
     *
     * @return	void
     */
    public function __construct()
    {
        $this->view = new HView(& $this);

        $this->view->baseUrl = HHttp::getBaseUrl();
        $this->view->realUrl = HHttp::getRealUrl();
        $this->view->escape  = 'htmlspecialchars';
        $this->view->title   = 'HLEN framework';
    }

    /*
     * Presmeruje na novou url
     *
     * @param	string	url - relativni
     * @param	boolean	zavolat po presmerovani exit
     * @return	void
     */
    public function redirect($url, $exit = true)
    {
        HHttp::headerRedirect(HHttp::getUrl() . $url);

        if ($exit) {
            exit;
        }
    }

    /*
     * Zachyti argument pro jeho automaticke pouziti v url
     *
     * @param	string	jmeno argumentu
     * @return	boolean
     */
    public function catchArg($name)
    {
        if (is_string($name) && isset(HRouter::$args[$name])) {
            if (!isset(HRouter::$replaceNamedArgs[$name])) {
                $this->catchedArgs[$name] = $name . ':' . HRouter::$args[$name];
            } else {
                $this->catchedArgs[$name] = HRouter::$args[$name];
            }
            return true;
        }
        return false;
    }

    /*
     * Recaller metody HApplication::error()
     */
    public function error()
    {
        $args = func_get_args();
        call_user_func_array(array('HApplication', 'error'), $args);
    }

    /*
     * Vytvori URL v ramci frameworku
     *
     * @param	string	jmeno controlleru
     * @param	string	jmeno action
     * @param	array	argumenty
     * @param	boolean	zdedit arguemnty (dedi se pouze zachycene jmenne argumenty!)
     * @param	string	pravidlo, podle ktereho se ma url vytvorit
     * @return	string
     */
    public function url($controller = null, $action = null, array $args = array(), $inherited = true, $rule = null)
    {
        $newUrl = array();

        if ($rule === null) {
            $newRule = HRouter::$rule;
        } else {
            $newRule = HHttp::urlToArray($rule);
        }

        foreach ($args as $name => $value) {
            if (is_string($name) && !isset(HRouter::$replaceNamedArgs[$name])) {
                $args[$name] = $name . ':' . $value;
            }
        }

        if ($inherited) {
            $args = array_merge($this->catchedArgs, $args);
        }
        
        foreach ($args as $key => $value) {
            if ($value === false) {
                unset($args[$key]);
            }
        }
        
        foreach ($newRule as $index => $value) {
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
                    } elseif($inherited) {
                        $newUrl[$index] = HRouter::$action;
                    } else {
                        $newUrl[$index] = 'index';
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

        while (!empty($args)) {
            $newUrl[] = array_shift($args);
        }
        
        return implode('/', $newUrl);
    }

    /*
     * Vrati pole se vsemi argumenty
     *
     * @return	array
     */
    public function getArgs()
    {
        return HRouter::$args;
    }

    /*
     * Vrati hodnotu jmenneho argumentu
     *
     * @param   string	jmeno argumentu
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

    /*
     * Spusti volani action a rendering
     *
     * @return  void
     */
    public function render()
    {
        static $run = false;

        if ($run === false) {
            $run = true;
            $actionName = HRouter::$action . 'Action';
            $methodExists = method_exists(get_class($this), $actionName);

            if (!$methodExists) {
                if (!HApplication::$error) {
                    HApplication::error('method');
                    $actionName = false;
                }
            } else {
                $this->view->view(HRouter::$action);
            }

            if (method_exists($this, 'init')) {
                call_user_func(array($this, 'init'));
            }

            if ($actionName !== false && $methodExists) {
                call_user_func_array(array($this, $actionName), HRouter::$args);
            }

            $this->view->render();
        }
    }

}