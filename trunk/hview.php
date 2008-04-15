<?php

/**
 * HLEN FRAMEWORK
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5 $WCREV$
 * @package     Hlen
 */


/**
 * Trida HView obstarava nacitani view a layoutu, ukladani promenych a take tvorbu odkazu
 */
class HView
{

    protected $ext = '.phtml';

    private $vars = array();
    private $viewPath;
    private $viewName;
    private $layoutPath;
    private $layoutName = 'layout';
    private $theme = false;
    private $absoluteView = false;


    /**
     * Nastaví view sablonu
     * Jmeno predavejte bez pripony
     * Pokud nechcete, aby Hlen doplnil adresarovnou strukturu sablone, predejte jako druhy parametr false
     *
     * @param   string jmeno sablony
     * @param   bool   nedoplnit adresarovou strukturu
     * @return  void
     */
    public function view($viewName, $absoluteView = false)
    {
        $this->viewName = $viewName;
        $this->absoluteView = $absoluteView;
    }

    /**
     * Nastavi layout sablonu
     * Jmeno predavejte bez pripony
     * Pokud zadate false, nepouzije se zadna sablona
     *
     * @param   string  jemeno sablony
     * @return  void
     */
    public function layout($layoutName)
    {
        $this->layoutName = $layoutName;
    }

    /**
     * Vrati jmeno view sablony, bez pripony
     *
     * @return  string
     */
    public function getView()
    {
        return $this->viewName;
    }

    /**
     * Vrati jmeno layout sablony, bez pripony
     *
     * @return  string
     */
    public function getLayout()
    {
        return $this->layoutName;
    }

    /**
     * Vrati cestu k view sablone
     *
     * @return  string
     */
    public function getViewPath()
    {
        return $this->viewPath;
    }

    /**
     * Vrati cestu k view layoutu
     *
     * @return  string
     */

    public function getLayoutPath()
    {
        return $this->layoutPath;
    }

    /**
     * Nastavi view tema
     * Temata vypnete pomoci false
     *
     * @param   string  jmeno tematu
     * @return  void
     */
    public function theme($themeName)
    {
        $this->theme = $themeName;
    }

    /**
     * Vrati jmeno tematu
     *
     * @return  mixed
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Naètení externího kodu
     *
     * @param   string  jmeno souboru bez pripony
     * @return  void
     */
    public function import($name)
    {
        extract($this->vars);
        $fileName = APP . "views/" . $this->getThemePath() . $name . $this->ext;
        if (file_exists($fileName)) {
            include $fileName;
        }
    }

    /**
     * Vytvori odkaz v zavislosti na systemovem routingu
     *
     * @param   string  text odkazu
     * @param   mixed   1) pole s paramtery pro funkci HController::url()
     *                  2) retezec s url
     * @param   array   pole s atributy
     * @return  string
     */
    public function link($title, $url = array(), array $attrs = array())
    {
        if (is_array($url)) {
            $url = call_user_func_array(array(HApplication::$controller, 'url'), $url);
        } else {
            $url = HHttp::getInternalUrl() . $url;
        }

        return HHtml::link($url, $title, $attrs);
    }

    /**
     * Vyrenderuje stranku z view a layoutu
     *
     * @return  void
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

    /**
     * Ulozi do seznamu promennych pro sablonu
     *
     * @param   string  jmeno promenne
     * @param   mixed   hodnota promenne
     * @return  bool
     */
    public function __set($name, $value)
    {
        if ($name === '') {
            return false;
        }
        $this->vars[$name] = $value;
        return true;
    }

    /**
     * Vrati hodnotu ze seznamu promennych pro sablonu
     *
     * @param   string  jmeno promenne
     * @return  mixed
     */
    public function __get($name)
    {
        if (isset($this->vars[$name])) {
            return $this->vars[$name];
        }
        return false;
    }

    /**
     * Parsuje sablonu
     *
     * @param   string  cesta k souboru
     * @param   array   pole s promennymi
     * @return  string
     */
    private function parse($parsedFile, $parsedVars)
    {
        extract($parsedVars);
        include $parsedFile;
        return ob_get_clean();
    }

    /**
     * Vrati cast cesty nastaveneho tematu
     *
     * @return string
     */
    private function getThemePath()
    {
        if ($this->theme === false) {
            return '';
        } else {
            return $this->theme . '/';
        }
    }

    /**
     * Vytvori cestu k view sablone
     * V pripade chyby vola prislusnou chybovou zpravu
     *
     * @return  void
     */
    private function makeViewPaths()
    {
        if (HApplication::$error) {
            $view = 'views/_errors/';
        } else {
            if (!$this->absoluteView) {
                $namespace = '';
                if (HRouter::$namespace !== false) {
                    $namespace = HRouter::$namespace . '_';
                }

                $view = 'views/' . $this->getThemePath() . HBasics::underscore($namespace . HRouter::$controller) . '/';
                if (!empty(HRouter::$service)) {
                    $view .= HRouter::$service . '/';
                }
            } else {
                $view = 'views/';
            }
        }

        $view .= HBasics::underscore($this->viewName) . $this->ext;
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
                HApplication::error('view', true);
                $this->makeViewPaths();
            }
        }
    }

    /**
     * Vytvori cestu k layout sablone
     * V nenalezeni layout sablony nastavi layout sablonu na false 
     *
     * @return  void
     */
    private function makeLayoutPaths()
    {
        $namespace = '';
        if (HRouter::$namespace !== false) {
            $namespace = HRouter::$namespace . '_';
        }

        $x = -1;
        $layouts[] = APP  . 'views/' . $this->getThemePath(). HBasics::underscore($namespace . $this->layoutName) . $this->ext;
        $layouts[] = CORE . 'views/' . HBasics::underscore($this->layoutName) . '.phtml';
        $layouts[] = APP  . 'views/layout' . $this->ext;

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

ob_start();