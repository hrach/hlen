<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Controller MVC aplikace
 *
 * Zakladni Controller poskytuje mnoho metod pro usnadneni prace
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.5
 */
class HController
{

    /** @var string */
    public $title;
    /** @var array */
    public $data = array();
    /** @var string */
    public $view;
    /** @var string */
    public $layout = "default";
    /** @var string */
    public $viewPath;
    /** @var string */
    public $layoutPath;

    /** @var array */    
    private $catchedArg = array();
    /** @var object */
    private $db = null;
    /** @var array */
    private $vars = array();

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->data = $_POST['data'];
    }

    /**
     * Pridani promenne pro sablonu
     *
     * @param string $var
     * @param mixed $val
     * @return void
     */
    public function set($var, $val)
    {
        $this->vars[$var] = $val;
    }

    /**
     * Precteni promenne pro sablonu
     *
     * @param string $var
     * @return mixed
     */
    public function get($var)
    {
        if (isset($this->vars[$var])) {
            return $this->vars[$var];
        } else {
            return false;
        }
    }

    /**
     * Presmerovani aplikace na novou url + jeji ukonceni
     *
     * @param string $url
     * @param boolean $exit = true
     */
    public function redirect($url, $exit = true)
    {
        HHttp::redirect(HHttp::getUrl() . $url);

        if ($exit) {
            exit;
        }
    }

    /**
     * Vola beforeRender kontroleru
     */
    private function callBeforeRender()
    {
        if (method_exists(HApplication::$controller, 'beforeRender')) {
            HApplication::$controller->beforeRender();
        }
    }

    /**
     * Vola afterRender kontroleru
     */
    private function callAfterRender()
    {
        if (method_exists(HApplication::$controller, 'afterRender')) {
            HApplication::$controller->afterRender();
        }
    }

    /**
     * Vygeneruje view
     */
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

        ob_end_clean();
    }
    
    /**
     * Zafixuje predavane promenne
     *
     * @param integer $name Jmeno fixovaneho argumentu
     */
    public function catchArg($name)
    {
        if (!empty(HRouter::$args[$name])) {
            $this->catchedArg[$name] = $name . HRouter::$naSeparator . HRouter::$args[$name];
        }
    }

    /**
     * Alias pro link
     *
     * @deprecated
     **/
    public function a($title, $url)
    {
        $url = HHttp::getBase() . $url;
        $el = new HHtml('a');
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }
          
    /**
     * Vrati odkaz
     *
     * @param string  $title
     * @param string  $url
     * @param string  $title = null
     * @param array   $options = array()
     * @return string
     */
    private function link($title, $url = array(), $inherited = true)
    {
        $url = HHttp::getBase() . $this->url($url, $inherited);
        $el = new HHtml('a');
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }

    /**
     * Vrati odpovidajici URL
     *
     * @param $url = array()
     * @param $inherited = true
     * @return string
     */
    public function url($url = array(), $inherited = true)
    {
        $newUrl = array();
        $rule = HRouter::$rule;
        $url[2] = (array) $url[2];

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
                        $newUrl[$index] = $base[$index];
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
    
    /**
     * Parsuje sablonu
     *
     * Vrati zpracovany obsah sablony
     * @param string $__File
     * @param array $__Vars
     * @return string
     */
    private function parse($__File, $__Vars)
    {
        extract($__Vars);
        include $__File;

        return ob_get_clean();
    }

    /**
     * Vytvori spravnou cestu pro view
     * 
     * @todo Vypis pri zacykleni
     */
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

    /**
     * Vytvori spravnou cestu pro layout
     */
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