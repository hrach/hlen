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


class HController extends HObject
{

    /** @var array */
    private $vars = array();

    /** @var string */
    public $title = null;
    /** @var array */
    public $data = null;

    /** @var string */
    public $view = "";
    /** @var string */
    public $layout = "default";

    /** @var string */
    protected $viewPath;
    /** @var string */
    protected $layoutPath;

    /**
     * constructor
     * @param void
     * @return void
     */
    function __construct()
    {
        $this->data = $_POST['data'];
    }

    /**
     * set var for template
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
     * get content of var for template
     *
     * @param string $name
     * @return mixed
     */
    public function read($name)
    {
        if(isset($this->vars[$name]))
            return $this->vars[$name];
        else
            return false;
    }

    /**
     * redirect on new url, and halt application
     *
     * @param string $url
     * @return void
     */
    public function redirect($url)
    {
        HHttp::redirect( HHttp::getUrl() . HApplication::systemUrl($url) );
        exit;
    }

    /**
     * call "beforeRender" methods
     *
     * @param viod
     * @return void
     */
    private function __callBeforeRender()
    {
        if(method_exists(HApplication::$controller, 'beforeRender'))
            HApplication::$controller->beforeRender();
    }

    /**
     * call "afterRender" methods
     * @param viod
     * @return void
     */
    private function __callAfterRender()
    {
        if(method_exists(HApplication::$controller, 'afterRender'))
            HApplication::$controller->afterRender();
    }

    /**
     * prepare path for View
     * @param viod
     * @return void
     */
    private function __makeViewPaths()
    {
        if(HApplication::$error) {
            $view = "views/_errors/";
        } else {
            $view = "views/".HRouter::$controller."/";
            if (!empty(HRouter::$service)) {
                $view .= HRouter::$service."/";
            }
        }

        $view .= HBasics::underscore($this->view).".php";

        if (HApplication::$system && file_exists(CORE.$view)) {
            $this->viewPath = CORE.$view;
        } elseif (file_exists(APP.$view)) {
            $this->viewPath = APP.$view;
        } else {
            throw new RuntimeException($view, 1003);
        }
    }

    /**
     * prepare path for Layout
     * @param viod
     * @return void
     */
    private function __makeLayoutPaths()
    {
        $layouts[] = APP . "views/" . HBasics::underscore($this->layout) . ".php";
        $layouts[] = CORE . "views/" . HBasics::underscore($this->layout) . ".php";
        $layouts[] = CORE . "views/default.php";

        foreach ($layouts as $x => $layout) {
            if (file_exists($layout)) {
                break;
            }
        }

        $this->layoutPath = $layouts[$x];
    }

    /**
     * render view
     * @param viod
     * @return void
     */
    public function renderView()
    {
        $this->__callBeforeRender();
        $this->__makeViewPaths();
        ob_start();

        $content = $this->parse($this->viewPath, $this->vars);
        
        $vars = array_merge( array('layout' => array('content' => $content, 'title' => $this->title) ), $this->vars );
        $this->__makeLayoutPaths();

        echo $this->parse($this->layoutPath, $vars);

        ob_end_clean();
        $this->__callAfterRender();
    }

    /**
     * parser - parse View file
     * @param string
     * @param array
     * @return string
     */
    protected function parse($__File, $__Vars)
    {
        extract($__Vars);
        include $__File;

        return ob_get_clean();
    }

}