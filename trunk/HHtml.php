<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Vytvari HTML elementy
 *
 * Vrstva pro objektovou praci s HTML
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HHtml implements ArrayAccess
{

    /** @var string */
    private $content;
    /** @var string */
    private $element;
    /** @var array */
    private $data = array();
    /** @var boolean */
    private $paar = true;
    /** @var array */
    private $emptyElements = array('img', 'hr', 'br', 'input', 'meta', 'area', 'base', 'col', 'link', 'param', 'frame', 'embed');

    /**
     * Konstruktor
     * 
     * @param string $element
     */
    function __construct($element)
    {
        $this->element = $element;
    }

    /**
     * Nastavi obsah elementu
     *
     * @param string $text
     */
    public function setContent($text)
    {
        $this->content = $text;
    }

    /**
     * Vrati obsah elementu
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Vrati element jako HTML
     *
     * @return string
     */
    public function get()
    {
        $el = $this->startTag()
            . $this->content
            . $this->endTag();

        return $el;
    }

    /**
     * Vrati pocatecni tag
     *
     * @return string
     */
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

    /**
     * Vrati koncovy tag
     *
     * @return string
     */
    public function endTag()
    {
        if (!in_array($this->element, $this->emptyElements)) {
            return '</' . $this->element . '>';
        }
    }

    /**
     * Ulozi atribut
     *
     * @param string  $key
     * @param mixed   $value
     */
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Vrati atribut
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        if ($this->check($key)) {
            return $this->data[$key];
        }
    }

    /**
     * Odstrani atribut
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        if ($this->check($key)) {
            unset($this->data[$key]);
        }
    }

    /**
     * Zkontroluje atriibut
     *
     * @param string $key
     * @param boolean
     */
    public function offsetExists($key)
    {
        return $this->check($key);
    }

    /**
     * Existuje atribut
     *
     * @param string $key
     * @return boolean
     */
    private function check($key)
    {
        if (!array_key_exists($key, $this->data)) {
            return false;
        }
        return true;
    }

    /**
     * Vrati atributy jako html retezec
     * 
     * @return string
     */
    private function getAttrs()
    {
        $attrs = '';
        foreach ($this->data as $key => $val) {
            $attrs .= " $key=\"$val\"";
        }
        return $attrs;
    }

}
