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

    private $vars = array();
    private $viewPath;
    private $viewName;
    private $layoutPath;
    private $layoutName = 'layout';


    /*
     * Kontruktor
     * Pøidá mezi promìnné referenci na controller
     * 
     * @param	HController	reference na controller
     * @return void
     */
    public function __construct(HController $controller)
    {
        $this->vars['controller'] = $controller;
    }

    /*
     * Nastaví view sablonu
     * Jmeno predavejte bez pripony
     * Pokud chcete urcit cestu k sablone primo, zadejte pred retezec znak |
     * 
     * @param	string jmeno sablony
     * @return	void 
     */
    public function view($viewName)
    {
        $this->viewName = $viewName;
    }

    /*
     * Nastavi layout sablonu
     * Jmeno predavejte bez pripony
     * Pokud zadate false, nepouzije se zadna sablona
     * 
     * @param	string	jemeno sablony
     * @return	void
     */
    public function layout($layoutName)
    {
        $this->layoutName = $layoutName;
    }

    /*
     * Vrati jmeno view sablony, bez pripony
     * 
     * @return	string
     */
    public function getView()
    {
        return $this->viewName;
    }

    /*
     * Vrati jmeno layout sablony, bez pripony
     * 
     * @return	string
     */
    public function getLayout()
    {
        return $this->viewName;
    }

    /*
     * Vrati cestu k view sablone
     * 
     * @return	string
     */
    public function getViewPath()
    {
        return $this->viewPath;
    }
    
    /*
     * Vrati cestu k view layoutu
     * 
     * @return	string
     */

    public function getLayoutPath()
    {
        return $this->layoutPath;
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
     * Recaller metody HController::url()
     */
    public function url()
    {
        $args = func_get_args();
        return call_user_func_array(array(HApplication::$controller, 'url'), $args);
    }
    
	/*
	 * Naètení externího kodu
	 * 
	 * @param	string	jmeno souboru bez pripony
	 * @return	void
	 */
    public function load($name)
    {
        extract($this->vars);
        $fileName = APP . "views/$name.phtml";
        if (file_exists($fileName)) {
            include $fileName;
        }
    }
    
	/*
	 * Vytvori odkaz v zavislosti na systemovem routingu
	 * 
	 * @param	string	text odkazu
	 * @param	mixed	1) pole s paramtery pro funkci HApplication::url()
	 * 					2) retezec s url
	 * @param	array	pole s atributy
	 * @return	string
	 */
    public function link($title, $url = array(), array $attributs = array())
    {
        if (!isset($url[3])) {
            $url[3] = true;
        }

        if (is_array($url)) {
            $url = HHttp::getBase() . HApplication::$controller->url(@$url[0], @$url[1], (array) @$url[2], @$url[3]);
        } else {
            $url = HHttp::getBase() . $url;
        }

        $el = new HHtml('a');
        foreach ($attributs as $atName => $atVal) {
            $el[$atName] = $atVal;
        }

        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }
    
	/*
	 * Vyrenderuje stranku z view a layoutu
	 * 
	 * @return	void
	 */
    public function render()
    {
        ob_start();
        $this->makeViewPaths();
        $this->vars['content'] = $this->parse($this->viewPath, $this->vars);

        if ($this->layoutName === false) {
            echo $this->vars['content'];
        } else {
            $this->makeLayoutPaths();
            echo $this->parse($this->layoutPath, $this->vars);
        }
    }

    /*
     * Ulozi do seznamu promennych pro sablonu
     * 
     * @param	string	jmeno promenne
     * @param	mixed	hodnota promenne
     * @return	boolean
     */
    public function __set($name, $value)
    {
        if ($name === '') {
            return false;
        }

        $this->vars[$name] = $value;
        return true;
    }
    
    /*
     * Vrati hodnotu ze seznamu promennych pro sablonu
     * 
     * @param	string	jmeno promenne
     * @return	mixed
     */
    public function __get($name)
    {
        if (isset($this->vars[$name])) {
            return $this->vars[$name];
        }

        return false;
    }

    /*
     * Parsuje sablonu
     * 
     * @param	string	cesta k souboru
     * @param	array	pole s promennymi
     * @return	string
     */
    private function parse($parsedFile, $parsedVars)
    {
        extract($parsedVars);
        include $parsedFile;
        return ob_get_clean();
    }
    
    /*
     * Vytvori cestu k view sablone
     * V pripade chyby vola prislusnou chybovou zpravu
     * 
     * @return	void
     */
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
        } elseif (HApplication::$error && file_exists(CORE . $view)) {
            $this->viewPath = CORE . $view;
        } else {
            if (HApplication::$error && $this->viewName == 'view') {
                die('Instalace frameworku je poskozena. Prosim, provedte aktualizaci knihoven a souboru. Chybi soubor: ' . $view);
            } else {
            	$this->missingView = $view;
                HApplication::error('view');
                $this->makeViewPaths();
            }
        }
    }

    /*
     * Vytvori cestu k layout sablone
     * V nenalezeni layout sablony nastavi layout sablonu na false 
     * 
     * @return	void
     */
    private function makeLayoutPaths()
    {
    	$x = -1;
        $layouts[] = APP . 'views/' . HBasics::underscore($this->layoutName) . '.phtml';
        $layouts[] = CORE . 'views/' . HBasics::underscore($this->layoutName) . '.phtml';
        $layouts[] = APP . 'views/layout.phtml';
        $layouts[] = CORE . 'views/layout.phtml';

        foreach ($layouts as $x => $layout) {
            if (file_exists($layout)) {
                break;
            }
        }

        if ($x === -1) {
        	$this->layoutName = false;
        } else {
	        $this->layoutPath = $layouts[$x];
        }
    }

}