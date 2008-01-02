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
 * @version   0.1.0
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
        HHttp::redirect(HHttp::getUrl() . HApplication::systemUrl($url));

        if ($exit) {
            exit;
        }
    }

    /**
     * Zajisti pripraveni HDb pred volanim metody
     */
    public function callBeforeMethod()
    {
        if (method_exists(HApplication::$controller, 'beforeMethod')) {
            HApplication::$controller->beforeMethod();
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
     * Vrati odkaz
     *
     * @param string  $url
     * @param string  $title = null
     * @param array   $options = array()
     * @return string
     */
    public function a($url, $title = null, $options = array())
    {
        $el = new HHtml('a');
        foreach ($options as $key => $val) {
            $el[$key] = $val;
        }

        $el['href'] = $this->url($url);
        $el->setContent(HBasics::getVal($title, $url));

        return $el->get();
    }

    /**
     * Vrati url
     *
     * @param string $url
     * @param boolean $absolute = false
     * @return string
     */
    public function url($url, $absolute = false)
    {
        if ($absolute || strpos($url, 'http://') === false) {
            $url = HHttp::getUrl() . HApplication::systemUrl($url);
        }

        return $url;
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