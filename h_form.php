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

require_once dirname(__FILE__).'/h_object.php';

class HForm extends HObject implements ArrayAccess
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

    /** @var array */
    public $submitedDataComplete = array();

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
    public function __construct($url = null)
    {
        if (class_exists('HApplication', false)) {
            $this->url = HHttp::getBase() . HApplication::systemUrl($url);
        } else {
            $this->url = HHttp::getBase() . $url;
        }
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
        $return = true;
        foreach ($this->data as $element)
        {
            if ( !$element->validate( $this->submitedData, $this ) ) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * add validate error
     *
     * @param string $message
     * @return void
     */
    public function addError($message)
    {
        $this->errors[] = $message;
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
        if (HHttp::getRequestMethod() === 'post') {
            $data = HHttp::getPost();
        } else {
            $data = HHttp::getGet();
        }

        foreach ($data as $id => $val) {
            $data[$id] = trim($val);
        }

        $dataComplete = $data;
        foreach ($this->data as $el)
        {
            if ($el->isSubmited($data))
            {
                foreach($this->data as $element)
                {
                    if ($data[$element->getId()] == $element->getEmptyValue()) {
                        $data[$element->getId()] = null;
                    }

                    if ( $element->getTag() === 'submit') {

                        $dataComplete[$element->getId()] = (bool) $dataComplete[$element->getId()];
                        unset( $data[$element->getId()] );

                    } elseif( $element->getTag() === 'select' ) {

                        if (!$element->has( $data[$element->getId()] )) {
                            $data[$element->getId()] = null;
                            $dataComplete[$element->getId()] = null;
                        }

                    }
                }

                $this->submitedData = $data;
                $this->submitedDataComplete = $dataComplete;
                return true;
            }
        }
        return false;
    }

    /**
     * get submited
     *
     * @param   boolean  $complete = false
     * @return  array
     */
    public function getSubmited($complete = false)
    {
        if ($complete) {
            return $this->submitedDataComplete;
        } else {
            return $this->submitedData;
        }
    }

    /**
     * set the default values
     *
     * @param mixed $defaults
     * @return void
     */
    public function setDefaults($defaults)
    {
        foreach ($defaults as $id => $val) {
            if (is_object($this->data[$id]) && $this->data[$id]->getTag() !== 'submit') {
                $this->data[$id]->setDefault($val);
            }
        }
    }

    /**
     * reset defaults values
     *
     * @param string
     * @return void
     */
    public function reSetDefaults()
    {
        foreach ($this->submitedDataComplete as $id => $val) {
            if ( is_object($this->data[$id])
                 && ( $this->data[$id]->getTag() !== 'submit'
                      || $this->data[$id]->getTag() !== 'password')
                ) {
                $this->data[$id]->setDefault($val);
            }
        }
    }

    /**
     * render
     *
     * @param void
     * @return void
     */
    public function render()
    {
        $render = $this->start();
        $render .= "<table>\n";
        foreach ($this->data as $row) {
            $render .= "<tr>\n";
            $render .= "<td>". $row->label ."</td>\n";
            $render .= "<td>". $row->element ."</td>\n";
            $render .= "</tr>\n";
        }
        $render .= "</table>\n";
        $render .= $this->end();
        return $render;
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

class HFormCondition extends HObject
{

    /** @var int */
    private $rule;

    /** @var $mixed*/
    private $arg;

    /** @var array */
    private $rules = array();

    /**
     * constructor
     *
     * @param int $rule
     * @param mixed $element
     * @param mixed $arg
     * @return void
     */
    public function __construct($rule = null, $arg = null)
    {
        $this->rule = $rule;
        $this->arg = $arg;

        return $this;
    }

    /**
     * can validate
     *
     * @param array $data
     * @param object $form
     * @param string $emptyValue
     * @return boolean
     */
    public function validate($value, $form, $emptyValue)
    {
        if (!empty($this->rule))
        {
            if ( is_object($this->arg) ) {
                $this->arg = $form->submitedData[ $this->arg->getId() ];
            }

            if ( !$this->validateField( $this->rule, $value, $this->arg, $emptyValue ) ) {
                return true;
            }
        }

        foreach ($this->rules as $rule)
        {
            if ( is_object($rule['arg']) ) {
                $arg = $form->submitedData[ $rule['arg']->getId() ];
            } else {
                $arg = $rule['arg'];
            }

            if( !$this->validateField( $rule['rule'], $value, $arg, $emptyValue ) ) {
                $form->addError( $rule['message'] );
                return false;
            }
        }

        return true;
    }

    /**
     * validate text value
     *
     * @param integer $rule
     * @param string $value
     * @param string $arg
     * @param string $emptyValue
     * @return boolean
     */
    private function validateField($rule, $value, $arg, $emptyValue)
    {
        if ($value == $emptyValue) {
            $value = null;
        }

        switch($rule)
        {
            case HForm::EQUAL:      return $value == $arg; break;
            case HForm::FILLED:     return !empty($value); break;
            case HForm::EMAIL:      return preg_match('/^[^@]+@[^@]+\.[a-z]{2,6}$/i', $value); break;
            case HForm::URL:        return preg_match('/^.+\.[a-z]{2,6}(\\/.*)?$/i', $value); break;
            case HForm::NUMERIC:    return is_numeric($value); break;
            case HForm::MINLENGTH:  return strlen($value) >= $arg; break;
            case HForm::MAXLENGTH:  return strlen($value) <= $arg; break;
            case HForm::LENGTH:     return strlen($value) == $arg; break;
        }

        return true;
    }

    /**
     * add Rule
     *
     * @param int $rule
     * @param string $message
     * @param mixed $arg
     * @return void
     */
    public function addRule($rule, $message, $arg = null)
    {
        $this->rules[] = array('rule' => $rule, 'message' => $message, 'arg' => $arg);
    }

}

class HFormElementInput extends HFormElement
{

    /**
     * constrctor
     *
     * @param string $type
     * @param string $id
     * @param mixed $label
     * @return void
     */
    public function __construct($type, $id, $label)
    {
        parent::__construct('input', $id, $label);
        $this->element['type'] = $type;
        $this->element['class'] = $type;
    }

    /**
     * get tag - return type
     *
     * @param void
     * @return string
     */
    public function getTag()
    {
        return $this->element['type'];
    }

}

class HFormElementTextArea extends HFormElement
{

    /**
     * constrctor
     *
     * @param string $type
     * @param string $id
     * @param mixed $label
     * @return void
     */
    public function __construct($id, $label)
    {
        parent::__construct('textarea', $id, $label);
    }

    /**
     * new set default method
     *
     * @param string $value
     * @return void
     */
    public function setDefault($value)
    {
        $this->element->setContent($value);
    }

    /**
     * new set the empty method
     *
     * @param string $value
     * @return void
     */
    public function setEmptyValue($value)
    {
        $this->element->setContent($value);
        $this->emptyValue = $value;
    }

}

class HFormElementSelect extends HFormElement
{

    /** @var array */
    private $options;

    /**
     * constrctor
     *
     * @param string $type
     * @param string $id
     * @param mixed $label
     * @return void
     */
    public function __construct($id, $label, $options)
    {
        parent::__construct('select', $id, $label);
        $this->createOptionTags($options);
    }

    /**
     * create option tags
     *
     * @param array $options
     * @return void
     */
    private function createOptionTags($options)
    {
        foreach($options as $key => $val)
        {
            $option = new HHtml('option');
            $option['value'] = $key;
            $option->setContent($val);

            $this->options[] = $option;
        }
    }

    /**
     * return html of options
     *
     * @param void
     * @return string
     */
    private function getOptionTags()
    {
        $ret = '';
        foreach($this->options as $option)
            $ret .= $option->get();
        return $ret;
    }

    /**
     * has string?
     *
     * @param  string  $value
     * @return boolean
     */
    public function has($value)
    {
        return in_array($value, $this->options);
    }

    /**
     * new set default method
     *
     * @param string $value
     * @return void
     */
    public function setDefault($value)
    {
        foreach($this->options as $option)
        {
            if($option['value'] == $value)
                $option['selected'] = 'selected';
        }
    }

    /**
     * new __get method
     *
     * @param string $name
     * @return void
     */
    public function __get($name)
    {
        if(empty($name))
            throw new LogicException("Cannot read an property without name");

        if($name === 'element')
            $this->element->setContent( $this->getOptionTags() );

        return parent::__get($name);
    }

}

class HFormElement
{

    /** @var HHtml */
    protected $label;

    /** @var HHtml */
    protected $element;

    /** @var string */
    protected $emptyValue;

    /** @var array */
    protected $conds = array();

    /**
     * constructor
     *
     * @param string
     * @return void
     */
    function __construct($tag, $id, $label, $options = null)
    {
        $this->tag = $tag;
        $id = HBasics::camelize($id);

        if($label)
        {
            $this->label = new HHtml('label');
            $this->label['for'] = "Form".$id;
            $this->label->setContent($label);
        }

        $this->element = new HHtml($tag);
        $this->element['class'] = $tag;
        $this->element['id'] = "Form". $id;
        $this->element['name'] = $id;
    }

    /**
     * set attribut
     *
     * @param string $var
     * @param string $val
     * @return void
     */
    public function set($var, $val)
    {
        $this->element[$var] = $val;
    }

    /**
     * set the empty value
     *
     * @param string $value
     * @return void
     */
    public function setEmptyValue($value)
    {
        $this->element['value'] = $value;
        $this->emptyValue = $value;
    }

    /**
     * get value which is equal state "empty"
     *
     * @param void
     * @return string
     */
    public function getEmptyValue()
    {
        return $this->emptyValue;
    }

    /**
     * is the value submited
     *
     * @param array $data
     * @return boolean
     */
    public function isSubmited($data)
    {
        $value = $data[$this->getId()];

        if ( $value !== $this->getEmptyValue() && !empty($value) ) {
            return true;
        }

        return false;
    }

    /**
     * get Id
     *
     * @param void
     * @return string
     */
    public function getId()
    {
        return $this->element['name'];
    }

    /**
     * get tag
     *
     * @param void
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * set default value
     *
     * @param string $value
     * @return void
     */
    public function setDefault($value)
    {
        $this->element['value'] = $value;
    }

    /**
     * add condition
     *
     * @param string $rule
     * @param mixed $arg
     * @return void
     */
    public function addCondition($rule, $arg)
    {
        return $this->conds[] = new HFormCondition($rule, $arg);
    }

    /**
     * add Rule
     *
     * @param integer $rule
     * @param string $message
     * @param mixed $arg
     * @return void
     */
    public function addRule($rule, $message, $arg)
    {
        $cond = new HFormCondition();
        $cond->addRule($rule, $message, $arg);

        $this->conds[] = $cond;
    }

    /**
     * validate element
     *
     * @param string $data
     * @param object $form
     * @return boolean
     */
    public function validate($data, $form)
    {
        $value = $data[ $this->getId() ];

        foreach ($this->conds as $cond)
        {
            if( !$cond->validate($value, $form, $this->getEmptyValue()) )
                return false;
        }

        return true;
    }

    /**
     * return html for element and label
     *
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        if ($name === '') {
            throw new LogicException("Cannot read an property without name");
        } elseif ($name === 'element') {
            return $this->element->get();
        } elseif ($name === 'label' && is_object($this->label)) {
            return $this->label->get();
        }
    }

    /**
     * caller
     *
     * @param string
     * @return void
     */
    public function __call($name, $args)
    {
        if ($name === '') {
            throw new LogicException("Cannot call a method without name");
        } elseif ($name === 'element') {
            foreach ($args[0] as $key => $arg) {
                $this->element[$key] = $arg;
            }
            return $this->element->get();
        } elseif ($name === 'label' && is_object($this->label)) {
            foreach ($args[0] as $key => $arg) {
                $this->label[$key] = $arg;
            }
            return $this->label->get();
        }
    }
}