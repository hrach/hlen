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

        if($label)
        {
            $this->label = new HHtml('label');
            $this->label['for'] = "Form".$id;
            $this->label->setContent($label);
        }

        $this->element = new HHtml($tag);
        $this->element['class'] = $tag;
        $this->element['id'] = "Form".$id;
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
    public function getEmpty()
    {
        return $this->emptyValue;
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
     * @param int $rule
     * @param string $message
     * @param mixed $arg
     * @return void
     */
    public function addRule($rule, $message, $arg)
    {
        $cond = new HFormCondition(0);
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
        foreach($this->conds as $cond)
        {
            if(!$cond->canValidate($data, $form)) continue;
            $rules = $cond->getRules();
            foreach($rules as $rule)
            {
                switch($rule['rule'])
                {
                    case HForm::EQUAL:
                        if(is_object($rule['arg']))
                            $arg = $form->submitedData[$rule['arg']->getId()];
                        else
                            $arg = $rule['arg'];

                        if($data !== $arg) {
                            $form->addError( $this->getId(), $rule['message'] );
                            return false;
                        }
                    break;
                    case HForm::FILLED:
                        if(empty($data)) {
                            $form->addError( $this->getId(), $rule['message'] );
                            return false;
                        }
                    break;
                    case HForm::EMAIL:
                        if(!ereg('[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}', $data)) {
                            $form->addError( $this->getId(), $rule['message'] );
                            return false;
                        }
                    break;
                }
            }
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
        if($name === '')
            throw new LogicException("Cannot read an property without name");
        elseif($name === 'element')
            return $this->element->get();
        elseif($name === 'label')
            return $this->label->get();
    }

}