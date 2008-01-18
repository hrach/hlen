<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


class HController
{

    public $title;
    public $data = array();
    public $view;
    public $layout = "default";
    public $viewPath;
    public $layoutPath;

    private $catchedArg = array();
    private $db = null;
    private $vars = array();


    public function set($var, $val)
    {
        $this->vars[$var] = $val;
    }

    public function get($var)
    {
        if (isset($this->vars[$var])) {
            return $this->vars[$var];
        } else {
            return false;
        }
    }

    public function redirect($url, $exit = true)
    {
        HHttp::redirect(HHttp::getUrl() . $url);

        if ($exit) {
            exit;
        }
    }

    public function catchArg($name)
    {
        if (!empty(HRouter::$args[$name])) {
            $this->catchedArg[$name] = $name . HRouter::$naSeparator . HRouter::$args[$name];
        }
    }

    public function a($title, $url, $attrs = array())
    {
        $url = HHttp::getBase() . $url;
        $el = new HHtml('a');
        
        foreach ($attrs as $atr => $val) {
            $el[$atr] = $val;
        }
        
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }
          
    private function link($title, $url = array(), $inherited = true)
    {
        $url = HHttp::getBase() . $this->url(@$url[0], @$url[1], @$url[2], $inherited);
        $el = new HHtml('a');
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }

    public function url($c = null, $a = null, $p = array(), $inherited = true)
    {
        $newUrl = array();
        $rule = HRouter::$rule;

        if (!is_array($p)) {
            $p = (array) $p;
        }

        foreach ($rule as $index => $val) {
            switch ($val) {
                case ':controller':
                    if (isset($c)) {
                        $newUrl[$index] = $c;
                    } elseif($inherited) {
                        $newUrl[$index] = HRouter::$controller;
                    } else {
                        $newUrl[$index] = HRouter::$defaultController;
                    }
                    break;
                case ':action':
                    if (isset($a)) {
                        $newUrl[$index] = $a;
                    } elseif($inherited) {
                        $newUrl[$index] = HRouter::$action;
                    } else {
                        $newUrl[$index] = HRouter::$defaultAction;
                    }                   
                    break;
                default:
                    if ($inherited) {
                        $newUrl[$index] = HRouter::getSegment($index);
                    } else {
                        $base = HHttp::urlToArray(HRouter::$base);
                        if (!empty($base[$index])) {
                            $newUrl[$index] = $base[$index];
                        } else {
                            continue;
                        }
                    }
                    break;
            }
        }
       
        foreach ($p as $i => $arg) {
            if (!is_integer($i)) {
                $p[$i] = $i . HRouter::$naSeparator . $arg;
            }
        }
        
        if ($inherited) {
            $args = array_merge($this->catchedArg, $p);
            foreach ($rule as $index => $val) {                            
                if ($val === ':arg') {
                    $newUrl[$index] = array_shift($args);
                    break;
                }
            }
            while (!empty($args)) {
                $newUrl[] = array_shift($args);
            }
        } else {
            foreach ($p as $arg) {
                $newUrl[] = $arg;
            }
        }

        return implode('/', $newUrl);
    }
    
        protected function getArgs()
    {
        return HRouter::$args;
    }

    protected function getArg($name)
    {
        if (isset(HRouter::$args[$name])) {
            return HRouter::$args[$name];
        } else {
            return false;
        }
    }
    
    public function render()
    {
        $actionName = HRouter::$action . 'Action';
        $methodExists = method_exists(get_class($this), $actionName);        
        
        if (!$methodExists) {
            if (!HApplication::$error) {
                HApplication::error('method');
                $actionName = false;
            }
        } else {
            $this->view = HRouter::$action;
        }

        if (method_exists($this, 'init')) {
            call_user_func(array($this, 'init'));
        }
        
        if ($actionName !== false) {
            call_user_func_array(array($this, $actionName), HRouter::$args);
        }
        
        $this->renderPage();
    }
    
    public function renderPage()
    {
        ob_start();
        $this->makeViewPaths();
        $content = $this->parse($this->viewPath, $this->vars);
        $vars = array_merge(array(
            'layout' => array(
                'content' => $content,
                'title' => $this->title,
            )
        ),$this->vars);
        $this->makeLayoutPaths();

        echo $this->parse($this->layoutPath, $vars);
    }
    
    private function parse($__File, $__Vars)
    {
        extract($__Vars);
        include $__File;

        return ob_get_clean();
    }

    private function makeViewPaths()
    {
        if (HApplication::$error) {
            $view = 'views/_errors/';
        } else {
            if ($this->view[0] !== '|') {
                $view = 'views/' . HRouter::$controller . '/';
                if (!empty(HRouter::$service)) {
                    $view .= HRouter::$service . '/';
                }
            } else {
                $view = 'views/';
                $this->view = substr($this->view, 1);
            }
        }

        $view .= HBasics::underscore($this->view) . '.phtml';

        if (file_exists(APP . $view)) {
            $this->viewPath = APP . $view;
        } elseif (HApplication::$system && file_exists(CORE . $view)) {
            $this->viewPath = CORE . $view;
        } else {
            if (HApplication::$error) {
                die('Instalace frameworku je poškozena. Prosím, proveďte aktualizaci knihoven a souborů. Chybí soubor: ' . $view);
            } else {
                HApplication::error('view');
                $this->makeViewPaths();
            }
        }
    }

    private function makeLayoutPaths()
    {
        $layouts[] = APP . "views/" . HBasics::underscore($this->layout) . ".phtml";
        $layouts[] = CORE . "views/" . HBasics::underscore($this->layout) . ".phtml";
        $layouts[] = CORE . "views/default.phtml";

        foreach ($layouts as $x => $layout) {
            if (file_exists($layout)) {
                break;
            }
        }

        $this->layoutPath = $layouts[$x];
    }    
    
}