<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.3
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/hview.php';


class HController
{

    private $catchedArg = array();


    public function __construct()
    {
        $this->view = new HView();

        $this->view->baseUrl = HHttp::getBase();
        $this->view->escape = 'htmlspecialchars';
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
            $this->catchedArg[$name] = $name . HRouter::$namedArgumentsSeparator . HRouter::$args[$name];
        }
    }

    public function a($title, $url, $attrs = array())
    {
        $url = $this->base . $url;
        $el = new HHtml('a');
        
        foreach ($attrs as $atr => $val) {
            $el[$atr] = $val;
        }
        
        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }

    public function link($title, array $url = array(), array $options = array())
    {
        if (!isset($url[3])) {
            $url[3] = true;
        }

        $url = $this->base . $this->url(@$url[0], @$url[1], (array) @$url[2], @$url[3]);
        $el = new HHtml('a');

        foreach ($options as $atName => $atVal) {
            $el[$atName] = $atVal;
        }

        $el['href'] = $url;
        $el->setContent($title);

        return $el->get();
    }

    public function url($c = null, $a = null, array $p = array(), $linkRule = true)
    {
        $newUrl = array();
        if ($linkRule === true) {
            $rule = HRouter::$rule;
        } elseif($linkRule === false) {
            $newRule = HHttp::urlToArray(HRouter::$baseRule);
            if ($newRule[count($newRule) - 1] == '*') {
                array_pop($newRule);
            }
            $rule = $newRule;
        } else {
            $newRule = HHttp::urlToArray($linkRule);
            if ($newRule[count($newRule) - 1] == '*') {
                array_pop($newRule);
            }
            $rule = $newRule;
        }

        foreach ($rule as $index => $val) {
            switch ($val) {
                case ':controller':
                    if (isset($c)) {
                        $newUrl[$index] = $c;
                    } elseif($linkRule !== false) {
                        $newUrl[$index] = HRouter::$controller;
                    } else {
                        $newUrl[$index] = HRouter::$defaultController;
                    }
                    break;
                case ':action':
                    if (isset($a)) {
                        $newUrl[$index] = $a;
                    } elseif($linkRule !== false) {
                        $newUrl[$index] = HRouter::$action;
                    } else {
                        $newUrl[$index] = HRouter::$defaultAction;
                    }
                    break;
                default:
                    if (is_bool($linkRule)) {
                        $newUrl[$index] = HRouter::getSegment($index);
                    } else {
                        if (!empty($base[$index])) {
                            $newUrl[$index] = $val;
                        } else {
                            continue;
                        }
                    }
                    break;
            }
        }

        foreach ($p as $i => $arg) {
            if (!is_integer($i)) {
                $p[$i] = $i . HRouter::$namedArgumentsSeparator . $arg;
            }
        }

        if ($linkRule !== false) {
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

    public function getArgs()
    {
        return HRouter::$args;
    }

    public function getArg($name)
    {
        if (isset(HRouter::$args[$name])) {
            return HRouter::$args[$name];
        } else {
            return false;
        }
    }

    public function renderElement($elementName)
    {
        ob_start();
        extract (call_user_func(array($this, $elementName . "Element")));
        require APP . 'views/_elements/' . HBasics::underscore($elementName) . '.phtml';
        return ob_get_clean();
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