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


    public function __construct()
    {

    }

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

    private function callBeforeRender()
    {
        if (method_exists(HApplication::$controller, 'beforeRender')) {
            HApplication::$controller->beforeRender();
        }
    }

    private function callAfterRender()
    {
        if (method_exists(HApplication::$controller, 'afterRender')) {
            HApplication::$controller->afterRender();
        }
    }

    public function renderView()
    {
        ob_start();

        $this->callBeforeRender();

        $this->makeViewPaths();
        $content = $this->parse($this->viewPath, $this->vars);
        
        $vars = array_merge(
            array(
                'layout' => array(
                    'content' => $content,
                    'title' => $this->title,
                )
            ),
            $this->vars
        );
        $this->makeLayoutPaths();

        echo $this->parse($this->layoutPath, $vars);

        $this->callAfterRender();
    }
    
    public function catchArg($name)
    {
        if (!empty(HRouter::$args[$name])) {
            $this->catchedArg[$name] = $name . HRouter::$naSeparator . HRouter::$args[$name];
        }
    }

    public function a($title, $url)
    {
        $url = HHttp::getBase() . $url;
        $el = new HHtml('a');
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }
          
    private function link($title, $url = array(), $inherited = true)
    {
        $url = HHttp::getBase() . $this->url($url, $inherited);
        $el = new HHtml('a');
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }

    public function url($url = array(), $inherited = true)
    {
        $newUrl = array();
        $rule = HRouter::$rule;

        if (empty($url[2])) {
            $url[2] = array();
        }
        
        if (!is_array($url[2])) {
            $url[2] = (array) $url[2];
        }

        foreach ($rule as $index => $val) {
            switch ($val) {
                case ':controller':
                    if (isset($url[0])) {
                        $newUrl[$index] = $url[0];
                    } elseif($inherited) {
                        $newUrl[$index] = HRouter::$controller;
                    } else {
                        $newUrl[$index] = HRouter::$defaultController;
                    }
                    break;
                case ':action':
                    if (isset($url[1])) {
                        $newUrl[$index] = $url[1];
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
       
        foreach ($url[2] as $i => $arg) {
            if (!is_integer($i)) {
                $url[2][$i] = $i . HRouter::$naSeparator . $arg;
            }
        }
        
        if ($inherited) {
            $args = array_merge($this->catchedArg, $url[2]);
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
            foreach ($url[2] as $arg) {
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
            $view = 'views/' . HRouter::$controller . '/';
            if (!empty(HRouter::$service)) {
                $view .= HRouter::$service . '/';
            }
        }

        $view .= HBasics::underscore($this->view) . '.phtml';

        if (file_exists(APP . $view)) {
            $this->viewPath = APP . $view;
        } elseif (HApplication::$system && file_exists(CORE . $view)) {
            $this->viewPath = CORE . $view;
        } else {
            if (HApplication::$error) {
                die('Nastalo zacyklení. Chybí soubor: ' . $view);
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