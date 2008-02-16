<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.3
 * @package    Hlen
 */


class HFormItem
{

    public $id;
    protected $form;
    protected $element;
    protected $emptyValue = null;
    protected $conds = array();
    protected $required = false;


    function __construct(HForm $form, $tag, $id)
    {
        $this->id = $id;
        $this->form = $form;
        $this->element = new HHtml($tag);

        $this->element['class'] = $tag;
        $this->element['id'] = 'form-' . $id;
        $this->element['name'] = $id;
    }

    public function setEmptyValue($value)
    {
        $this->element->setContent($value);
        $this->emptyValue = $value;
    }

    public function getEmptyValue()
    {
        return $this->emptyValue;
    }

    public function getValue($name)
    {
        if (isset($this->element[$name])) {
            return $this->element[$name];
        } else {
            return false;
        }
    }

    public function setValue($name, $val)
    {
        $this->element[$name] = $val;
    }

    public function isSubmited($value)
    {
        if (!empty($value)) {
            return true;
        }
        return false;
    }

    public function setDefault($value)
    {
        $this->element->setContent($value);
    }

    public function getDefault()
    {
        return $this->element->getContent();
    }

    public function addCondition($rule, $arg)
    {
        return $this->conds[] = new HFormCondition($rule, $this->form, $this, $arg);
    }

    public function addRule($rule, $message, $arg)
    {
        if ($rule == HForm::FILLED) {
            $this->required = true;
        }

        $cond = new HFormCondition(null, $this->form, $this);
        $cond->addRule($rule, $message, $arg);

        $this->conds[] = $cond;
    }

    public function isValid($value)
    {
        foreach ($this->conds as $cond) {
            if (!$cond->isValid($value)) {
                return false;
            }
        }
        return true;
    }

    public function label($label, array $attributs = array())
    {
        $labelEl = new HHtml('label');
        $labelEl['for'] = 'form-' . $this->id;
        $labelEl['id'] = 'form-' . $this->id . '-label';
        $labelEl->setContent($label);

        foreach ($attributs as $key => $val) {
            $labelEl[$key] = $val;
        }

        if ($this->required) {
            $labelEl['class'] .= ' required';
        }

        return $labelEl->get();
    }

    public function element(array $attributs = array())
    {
        foreach ($attributs as $key => $val) {
            $this->element[$key] = $val;
        }

        return $this->element->get();
    }
    
    public function __get($name)
    {
        if ($name === 'element') {
            return $this->element();
        }
    }

}


class HFormTextItem extends HFormItem
{

    public $trim = true;


    public function __construct($form, $id)
    {
        parent::__construct($form, 'input', $id);
        $this->element['type'] = 'text';
        $this->element['class'] = 'text';
    }

    public function setEmptyValue($value)
    {
        $this->element['value'] = $value;
        $this->emptyValue = $value;
    }

    public function setDefault($value)
    {
        $this->element['value'] = $value;
    }

    public function getDefault()
    {
        return $this->element['value'];
    }

}


class HFormTextPasswordItem extends HFormTextItem
{

    public function __construct($form, $id)
    {
        parent::__construct($form, $id);
        $this->element['type'] = 'password';
    }

}


class HFormTextHiddenItem extends HFormTextItem
{

    public function __construct($form, $id)
    {
        parent::__construct($form, $id);
        $this->element['type'] = 'hidden';
        unset($this->element['class']);
    }

    public function label()
    {}

}


class HFormFileItem extends HFormTextItem
{

    public $trim = false;


    public function __construct($form, $id)
    {
        parent::__construct($form, $id);
        $this->element['type'] = 'file';
    }

}


class HFormTextAreaItem extends HFormItem
{

    public $trim = false;


    public function __construct($form, $id)
    {
        parent::__construct($form, 'textarea', $id);
    }

}


class HFormSelectItem extends HFormItem
{

    public $trim = true;
    private $options = array();
    private $optionsVals = array();


    public function __construct($form, $id, $options)
    {
        parent::__construct($form, 'select', $id);

        $this->optionsVals = $options;

        foreach ($options as $key => $val) {
            $option = new HHtml('option');
            $option['value'] = $key;
            $option->setContent($val);

            $this->options[] = $option;
        }
    }

    public function setDefault($value)
    {
        foreach ($this->options as $option) {
            if ($option['value'] == $value) {
                $option['selected'] = 'selected';
            }
        }
    }

    public function existsVal($value)
    {
        return array_key_exists($value, $this->optionsVals);
    }

    public function element(array $attributs = array())
    {
        $options = '';
        foreach ($this->options as $option) {
            $options .= $option->get();
        }

        $this->element->setContent($options);
        return parent::element($attributs);
    }

}


class HFormSubmitItem extends HFormTextItem
{

    public function __construct($form, $id)
    {
        parent::__construct($form, $id);
        $this->element['type'] = 'submit';
        $this->element['class'] = 'submit';
    }

    public function setDefault()
    {}

    public function element($value = null, array $attributs = array())
    {
        if ($value) {
            $this->element['value'] = $value;
        }
        return parent::element($attributs);
    }

}


class HFormCheckBoxItem extends HFormItem
{

    public $trim = true;


    public function __construct($form, $id)
    {
        parent::__construct($form, 'input', $id);
        $this->element['type'] = 'checkbox';
        $this->element['class'] = 'checkbox';
        $this->element['value'] = 'true';
    }

    public function setDefault($value)
    {
        if ($this->element['value'] == $value) {
            $this->element['checked'] = 'checked';
        }
    }

    public function getDefault()
    {
        return (boolean) $this->element['checked'];
    }

}


class HFormMultiCheckBoxItem extends HFormCheckBoxItem
{

    private $value;


    public function __construct($form, $id, $key, $value)
    {
        parent::__construct($form, $id);
        $this->element['value'] = $key;
        $this->element['id'] = 'form-' . $id . $key;
        $this->element['name'] = $id . '[]';
        $this->value = $value;
    }

    public function label(array $attributs = array())
    {
        $labelEl = new HHtml('label');
        $labelEl['for'] = $this->element['id'];
        $labelEl->setContent($this->value);

        foreach ($attributs as $key => $val) {
            $labelEl[$key] = $val;
        }

        return $labelEl->get();
    }

}


class HFormMultiCheckBox implements Iterator
{

    public $id;
    public $trim = false;

    private $form;
    private $boxes = array();
    private $boxesVals = array();


    function __construct($form, $id, array $boxes)
    {
        $this->id = $id;
        $this->form = $form;
        $this->boxesVals = $boxes;

        foreach ($boxes as $key => $value) {
            $this->boxes[$key] = new HFormMultiCheckBoxItem($form, $id, $key, $value);
        }
    }

    public function isSubmited($value)
    {
        if (!empty($value) && is_array($value)) {
            return true;
        }
        return false;
    }

    public function setDefault($boxes)
    {
        foreach ($boxes as $box) {
            $this->boxes[$box]->setValue('checked', true);
        }
    }

    public function getDefault()
    {
        $def = array();
        foreach ($this->boxes as $key => $box) {
            if ($box->getValue('checked') == 'checked') {
                $def[] = $this->boxes[$key]->value;
            }
        }
        return $def;
    }

    public function isValid(& $value)
    {
        foreach ((array) $value as $key => $val) {
            if (!array_key_exists($val, $this->boxesVals)) {
                unset($value[$key]);
            }
        }
        return true;
    }

    public function getEmptyValue()
    {
        return null;
    }

    public function current()
    {
        return current($this->boxes);
    }

    public function key()
    {
        return key($this->boxes);
    }

    public function next()
    {
        next($this->boxes);
    }

    public function rewind()
    {
        reset($this->boxes);
    }

    public function valid()
    {
        return (current($this->boxes) !== FALSE);
    }
    
}