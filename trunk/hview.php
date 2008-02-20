<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */


class HView
{

    public $controller;

    private $vars = array();

    private $viewPath;
    private $layoutPath;

    private $viewName;
    private $layoutName = 'layout';


    public function view($viewName)
    {
        $this->viewName = $viewName;
    }

    public function layout($layoutName)
    {
        $this->layoutName = $layoutName;
    }

    public function error($error = '404')
    {
        HApplication::error($error);
    }

    public function getView()
    {
        return $this->viewName;
    }

    public function getLayout()
    {
        return $this->viewName;
    }

    public function getViewPath()
    {
        return $this->viewPath;
    }

    public function getLayoutPath()
    {
        return $this->layoutPath;
    }

    public function link()
    {
        $args = func_get_args();
        return call_user_func_array(array(HApplication::$controller, 'link'), $args);
    }

    public function url()
    {
        $args = func_get_args();
        return call_user_func_array(array(HApplication::$controller, 'url'), $args);
    }

    public function getArg()
    {
        $args = func_get_args();
        return call_user_func_array(array(HApplication::$controller, 'getArg'), $args);
    }

    public function renderElement($name)
    {
        $fileName = APP . "views/_elements/$name.phtml";
        return $this->parse($fileName, $this->vars);
    }

    public function render()
    {
        ob_start();
        $this->makeViewPaths();
        $this->vars['content'] = $this->parse($this->viewPath, $this->vars);
        $this->makeLayoutPaths();

        echo $this->parse($this->layoutPath, $this->vars);
    }

    protected function parse($parsedFile, $parsedVars)
    {
        extract($parsedVars);
        include $parsedFile;
        return ob_get_clean();
    }

    protected function __set($name, $value)
    {
        if ($name === '') {
            return false;
        }

        $this->vars[$name] = $value;
        return true;
    }

    private function makeViewPaths()
    {

        if (HApplication::$error) {
            $view = 'views/_errors/';
        } else {
            if ($this->viewName[0] !== '|') {
                $view = 'views/' . HRouter::$controller . '/';
                if (!empty(HRouter::$service)) {
                    $view .= HRouter::$service . '/';
                }
            } else {
                $view = 'views/';
                $this->viewName = substr($this->viewName, 1);
            }
        }

        $view .= HBasics::underscore($this->viewName) . '.phtml';

        $this->viewPath = $view;

        if (file_exists(APP . $view)) {
            $this->viewPath = APP . $view;
        } elseif (HApplication::$system && file_exists(CORE . $view)) {
            $this->viewPath = CORE . $view;
        } else {
            if (HApplication::$error && $this->viewName == 'view') {
                die('Instalace frameworku je poškozena. Prosím, proveďte aktualizaci knihoven a souborů. Chybí soubor: ' . $view);
            } else {
                HApplication::error('view');
                $this->makeViewPaths();
            }
        }
    }

    private function makeLayoutPaths()
    {
        $layouts[] = APP . 'views/' . HBasics::underscore($this->layoutName) . '.phtml';
        $layouts[] = CORE . 'views/' . HBasics::underscore($this->layoutName) . '.phtml';
        $layouts[] = APP . 'views/layout.phtml';
        $layouts[] = CORE . 'views/layout.phtml';

        foreach ($layouts as $x => $layout) {
            if (file_exists($layout)) {
                break;
            }
        }

        $this->layoutPath = $layouts[$x];
    }


}