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


final class HLoader {

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function __callBeforeRender()
    {
        foreach($this->controller->components as $name)
            if(method_exists($this->controller->$name, 'beforeRender'))
                $this->controller->$name->beforeRender();
        foreach($this->controller->helpers as $name)
            if(method_exists($this->controller->vars[$name], 'beforeRender'))
                $this->controller->vars[$name]->beforeRender();

    }

    public function __callAfterRender()
    {
        foreach($this->controller->components as $name)
            if(method_exists($this->controller->$name, 'afterRender'))
                $this->controller->$name->afterRender();
        foreach($this->controller->helpers as $name)
            if(method_exists($this->controller->vars[$name], 'afterRender'))
                $this->controller->vars[$name]->afterRender();
    }

    /*
     * load helper
     * @param mixed
     * @return void
     */
    public function helper($helpers)
    {
        foreach((array)$helpers as $name) {
            if(in_array($name, $this->controller->helpers)) continue;

            if($this->loadHelper($name) === false) continue;

            $nameClass = $name."Helper";
            $name = strtolower($name);
            $this->controller->helpers[] = $name;
            $this->controller->set($name, new $nameClass);
        }
    }

    /*
     * load component
     * @param mixed
     * @return void
     */
    public function component($components)
    {

    }

    /*
     * make error view
     * @param string
     * @return void
     */
    public function error($code)
    {
        $this->controller->view = $code;
        Router::$error = true;
    }

    /*
     * load helper
     * @param string
     * @return boolean
     */
    private function loadHelper($helperName)
    {
        $ret = load(APP."views/_helpers/".underscore($helperName).".php") ||
               load(HLEN_CORE."views/_helpers/".underscore($helperName).".php");
        return $ret;
    }

    /*
     * load component
     * @param string
     * @return boolean
     */
    private function loadComponent($componentName)
    {
        $ret = load(APP."components/".underscore($componentName)."/".underscore($componentName).".php") ||
               load(HLEN_CORE."components/".underscore($componentName)."/".underscore($componentName).".php");
        return $ret;
    }
}