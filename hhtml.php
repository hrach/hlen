<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */


class HHtml implements ArrayAccess
{

    private $content;
    private $element;
    private $data = array();
    private $paar = true;
    private $emptyElements = array(
        'img', 'input', 'meta', 'area',
        'base', 'col', 'link', 'param', 'frame', 'embed'
    );


    /*
     * Vytvori html odkaz
     * Pokud nezadate druhy parametr, pouzije se jako text odkazu jeho url
     * 
     * @param	string	url
     * @param	string	text odkazu
     * @return	string
     */
    public static function link($href, $text = null) {
        $link = new HHtml('a');
        $link['href'] = $href;

        if ($text === null) {
            $link->setContent($href);
        } else {
            $link->setContent($text);
        }

        return $link->get();
    }

    function __construct($element)
    {
        $this->element = $element;
    }

    public function setContent($text)
    {
        $this->content = $text;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function get()
    {
        return $this->startTag() . $this->content . $this->endTag();
    }

    public function startTag()
    {
        $el = '<' . $this->element . $this->getAttrs();

        if (in_array($this->element, $this->emptyElements)) {
            $el .= '/>';
        } else {
            $el .= '>';
        }

        return $el;
    }

    public function endTag()
    {
        if (!in_array($this->element, $this->emptyElements)) {
            return '</' . $this->element . '>';
        }
    }

    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function offsetGet($key)
    {
        if ($this->check($key)) {
            return $this->data[$key];
        }
        
        return false;
    }

    public function offsetUnset($key)
    {
        if ($this->check($key)) {
            unset($this->data[$key]);
        }
    }

    public function offsetExists($key)
    {
        return $this->check($key);
    }

    private function check($key)
    {
        if (!array_key_exists($key, $this->data)) {
            return false;
        }
        return true;
    }

    private function getAttrs()
    {
        $attrs = '';
        foreach ($this->data as $key => $val) {
            $attrs .= " $key=\"$val\"";
        }
        return $attrs;
    }

}