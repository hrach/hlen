<?php

class HElement implements ArrayAccess
{

    /** @var string */
    private $content;

    /** @var string */
    private $element;

    /** @var array */
    private $data;

    /** @var boolean */
    private $paar;

    /** @var array */
    public $emptyElements = array('img', 'hr', 'br', 'input', 'meta', 'area', 'base', 'col', 'link', 'param', 'frame', 'embed');

    /**
     * constructor
     * 
     * @param string $element
     * @param boolean $paar
     * @return void
     */
    function __construct($element)
    {
        $this->element = $element;
    }

    /**
     * set the content of element
     *
     * @param string $text
     * @return void
     */
    public function setContent($text)
    {
        $this->content = $text;
    }

    /**
     * get content of element
     *
     * @param void
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * return html string
     *
     * @param void
     * @return string
     */
    public function get()
    {
        $el = $this->startTag();
        $el .= $this->content;
        $el .= $this->endTag();

        return $el;
    }

    /**
     * start tag
     *
     * @param void
     * @return string
     */
    public function startTag()
    {
        $el = "<". $this->element . $this->getAttrs();

        if(in_array($this->element, $this->emptyElements))
            $el .= " />";
        else
            $el .= ">";

        return $el;
    }

    /**
     * end tag
     *
     * @param void
     * @return string
     */
    public function endTag()
    {
        if(!in_array($this->element, $this->emptyElements))
            return "</". $this->element .">";
    }

    /**
     * return html string of attributes
     * 
     * @param void
     * @return string
     */
    private function getAttrs()
    {
        $attrs = '';
        foreach($this->data as $key => $val)
        {
            $attrs .= " $key=\"$val\"";
        }
        return $attrs;
    }

    /**
     * save attribut
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * return attribut
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        $this->check($key, true);
        return $this->data[$key];
    }

    /**
     * remove attribut
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $this->check($key, true);
        unset($this->data[$key]);
    }

    /**
     * check attribut
     *
     * @param string $key
     * @param boolean
     */
    public function offsetExists($key)
    {
        return $this->check($key);
    }

    /**
     * check key -> in list of attributs
     *
     * @param string $key
     * @return boolean
     */
    private function check($key)
    {
        if (!array_key_exists($key, $this->data))
            return false;
        return true;
    }

}