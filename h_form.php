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

require_once dirname(__FILE__).'/h_form_elements.php';
require_once dirname(__FILE__).'/h_form_condition.php';

class HForm implements ArrayAccess
{

    /** @var consts */
    const
        EQUAL = 200,
        FILLED = 201,
        NUMERIC = 202,
        MINLENGTH = 203,
        MAXLENGTH = 204,
        LENGTH = 205,
        EMAIL = 206,
        URL = 207;

    /** @var string */
    private $url;

    /** @var array */
    private $rules = array();

    /** @var array */
    private $data = array();

    /** @var array */
    public $submitedData = array();

    /** @var HElement */
    private $formElement;

    /** @var string */
    private $method = 'post';

    /** @var array */
    private $errors = array();

    /**
     * construcotr
     *
     * @param void
     * @return void
     */
    public function __construct($url = '')
    {
        if(class_exists('HApplication', false))
            $this->url = HHttp::getBase() . HApplication::systemUrl($url);
        else
            $this->url = HHttp::getBase() . $url;
    }

    /**
     * add input text
     *
     * @param string $id
     * @param mixed $label
     * @return HFormElement
     */
    public function addText($id, $label = false)
    {
        $this->data[$id] = new HFormElementInput('text', $id, $label);
        return $this->data[$id];
    }

    /**
     * add hidden input
     *
     * @param string $id
     * @return HFormElement
     */
    public function addHidden($id)
    {
        $this->data[$id] = new HFormElementInput('hidden', $id, false);
        return $this->data[$id];
    }

    /**
     * add textarea
     *
     * @param string $id
     * @param mixed $label
     * @return HFormElement
     */
    public function addTextArea($id, $label = false)
    {
        $this->data[$id] = new HFormElementTextArea($id, $label);
        return $this->data[$id];
    }

    /**
     * add select
     *
     * @param string $id
     * @param mixed $label
     * @param array $options
     * @return HFormElement
     */
    public function addSelect($id, $label, $options)
    {
        $this->data[$id] = new HFormElementSelect($id, $label, $options);
        return $this->data[$id];
    }

    /**
     * add submit button
     *
     * @param string $id
     * @param mixed $value
     * @return HFormElement
     */
    public function addSubmit($id, $label = false)
    {
        $this->data[$id] = new HFormElementInput('submit', $id, false);
        if($label)
            $this->data[$id]->set('value', $label);
        return $this->data[$id];
    }

    /**
     * form's start tad
     *
     * @param string $action
     * @param array $options
     * @return string
     */
    public function start()
    {
        $this->formElement = new HHtml('form');
        $this->formElement['action'] = $this->url;
        $this->formElement['method'] = $this->method;

        return $this->formElement->startTag();
    }

    /**
     * form's end tag
     * 
     * @param void
     * @return string
     */
    public function end()
    {
        return $this->formElement->endTag();
    }

    /**
     * add rule
     *
     * @param string $id
     * @param string $rule
     * @param string $message
     * @param string $params
     * @return void
     */
    public function addRule($id, $rule, $message, $arg = null)
    {
        $this->data[$id]->addRule($rule, $message, $arg);
    }

    /**
     * add condition
     *
     * @param string $id
     * @param int $rule
     * @return void
     */
    public function addCondition($id, $rule, $arg = null)
    {
        return $this->data[$id]->addCondition($rule, $arg);
    }

    /**
     * validating
     *
     * @param void
     * @return boolean
     */
    public function validate()
    {
        $ret = true;
        foreach($this->data as $element)
        {
            if(!$element->validate($this->submitedData[$element->getId()], $this))
                $ret = false;
        }
        return $ret;
    }

    /**
     * add validate error
     *
     * @param string $id
     * @param string $message
     * @return void
     */
    public function addError($id, $message)
    {
        $this->errors[] = array('id' => $id, 'message' => $message);
    }

    /**
     * get list of errors
     *
     * @param void
     * @return string
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * was form submited?
     *
     * @param void
     * @return boolean
     */
    public function isSubmited()
    {
        if(HHttp::getRequestMethod() === 'post')
            $data = HHttp::getPost();
        else
            $data = HHttp::getGet();

        foreach($this->data as $el)
        {
            if(!empty($data[$el->getId()]) && $data[$el->getId()] != $el->getEmpty())
            {
                $this->submitedData = $data;
                $submited = array();
                foreach($this->data as $el)
                {
                    switch($el->getTag())
                    {
                        case 'submit':
                            $submited[$el->getId()] = (bool) $this->submitedData[$el->getId()];
                        break;
                        default:
                            $submited[$el->getId()] = $this->submitedData[$el->getId()];
                        break;
                    }
                }
                $this->submitedData = $submited;
                return true;
            }
        }
        return false;
    }

    /**
     * get submited
     *
     * @param void
     * @return array
     */
    public function getSubmited()
    {
        return $this->submitedData;
    }

    /**
     * set the default values
     *
     * @param mixed $defaults
     * @return void
     */
    public function setDefaults($defaults)
    {
        foreach($defaults as $id => $val)
            if(is_object($this->data[$id]))
                $this->data[$id]->setDefault($val);
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