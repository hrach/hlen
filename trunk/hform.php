<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/hhttp.php';
require_once dirname(__FILE__) . '/hhtml.php';
require_once dirname(__FILE__) . '/hform_items.php';
require_once dirname(__FILE__) . '/hform_conditions.php';


class HForm implements ArrayAccess
{

    const
        EQUAL = 200,
        FILLED = 201,
        NUMERIC = 202,
        MINLENGTH = 203,
        MAXLENGTH = 204,
        LENGTH = 205,
        EMAIL = 206,
        URL = 207;

    public $data = array();
    private $elements = array();
    private $formElement;
    private $errors = array();


    public function __construct($url = null, $method = 'post')
    {
        $this->formElement = new HHtml('form');
        $this->formElement['action'] = HHttp::getBase() . $url;
        $this->formElement['method'] = $method;
    }

    public function addText($id)
    {
        $this->elements[$id] = new HFormTextItem($this, $id);
        return $this;
    }

    public function addPassword($id)
    {
        $this->elements[$id] = new HFormTextPasswordItem($this, $id);
        return $this;
    }

    public function addFile($id)
    {
        $this->formElement['enctype'] = 'multipart/form-data'; 

        $this->elements[$id] = new HFormFileItem($this, $id);
        return $this;
    }

    public function addHidden($id)
    {
        $this->elements[$id] = new HFormTextHiddenItem($this, $id);
        return $this;
    }

    public function addTextArea($id)
    {
        $this->elements[$id] = new HFormTextAreaItem($this, $id);
        return $this;
    }

    public function addSelect($id, $options)
    {
        $this->elements[$id] = new HFormSelectItem($this, $id, $options);
        return $this;
    }

    public function addSubmit($id = 'submit')
    {
        $this->elements[$id] = new HFormSubmitItem($this, $id);
        return $this;
    }
    
    public function addCheckBox($id)
    {
        $this->elements[$id] = new HFormCheckBoxItem($this, $id);
        return $this;
    }
    
    public function addMultiCheckBox($id, $boxes)
    {
        $this->elements[$id] = new HFormMultiCheckBox($this, $id, $boxes);
        return $this;
    }

    public function renderStart()
    {
        return $this->formElement->startTag();
    }

    public function renderEnd()
    {
        return $this->formElement->endTag();
    }

    public function isSubmited()
    {
        $return = false;
        $submitData = array();

        if (HHttp::getRequestMethod() === 'post') {
            $data = HHttp::getPost();
        } else {
            $data = HHttp::getGet();
        }

        foreach ($this->elements as $id => $element) {

            $class = get_class($element);

            if ($class == 'HFormFileItem' && isset($_FILES[$id])) {
                $submitData[$id] = $_FILES[$id];
            } elseif (isset($data[$id])) {
                $submitData[$id] = $data[$id];
            } else {
                continue;
            }

            $value = & $submitData[$id];

            if ($element->trim) {
                $submitData[$id] = trim($value);
            }

            if ($element->getEmptyValue() == $value) {
                $submitData[$id] = null;
            }

            if ($element->isSubmited($value)) {

                switch ($class) {
                    case 'HFormSubmitItem':
                        unset($submitData[$id]);
                    break;
                    case 'HFormSelectItem':
                        if (!$element->existsVal($value)) {
                            unset($submitData[$id]);
                        }
                    break;
                }

                $return = true;

            }
        }

        if ($return) {
            $this->data = $submitData;
        }

        return $return;
    }

    public function isValid()
    {
        $return = true;

        foreach ($this->elements as $id => $element) {
            if (!$element->isValid(@$this->data[$id])) {
                $return = false;
            }
        }

        return $return;
    }

    public function setDefaults($defaults)
    {
        foreach ($defaults as $id => $value) {
            if (array_key_exists($id, $this->elements)) {
                $this->elements[$id]->setDefault($value);
            }
        }
    }

    public function reSetDefaults()
    {
        foreach ($this->data as $id => $value) {
            $element = $this->elements[$id];
            if (array_key_exists($id, $this->elements) && get_class($element) !== 'HFormTextPasswordItem') {
                if ($value == null && $element->getEmptyValue() !== null) {
                    $this->elements[$id]->setDefault($element->getEmptyValue());
                } else {
                    $this->elements[$id]->setDefault($value);
                }
            }
        }
    }

    public function addRule($id, $rule, $message, $arg = null)
    {
        $this->elements[$id]->addRule($rule, $message, $arg);
        return $this;
    }

    public function addCondition($id, $rule, $arg = null)
    {
        return $this->elements[$id]->addCondition($rule, $arg);
    }

    public function addError($message)
    {
        $this->errors[] = $message;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorList()
    {
        if (empty($this->errors)) {
            return null;
        }

        $list = '<ul>';
        foreach ($this->errors as $error) {
            $list .= "<li>$error</li>";
        }
        $list .= '</ul>';

        return $list;
    }

    public function getSubmited()
    {
        return $this->data;
    }

    public function render()
    {
        $render = $this->getErrorList()
                . $this->renderStart() . "<table>\n";

        foreach ($this->elements as $el) {
            
            if (get_class($el) !== 'HFormSubmitItem') {
                $render .= "<tr>\n"
                         . '<td>' . $el->label($el->id) . "</td>\n"
                         . '<td>' . $el->element . "</td>\n"
                         . "</tr>\n";
            } else {
                $render .= "<tr>\n<td></td>\n"
                         . '<td>' . $el->element($el->id) . "</td>\n"
                         . "</tr>\n";
            }

        }

        $render .= "</table>\n"
                .  $this->renderEnd();

        return $render;
    }

    public function renderCode($formName = 'form')
    {
        $render = '
        <pre>
        &lt;?=$' . $formName . '->getErrorList()?>
        &lt;br />
        &lt;?=$' . $formName . '->renderStart()?>
        ';

        foreach ($this->elements as $el) {
            if (get_class($el) !== 'HFormSubmitItem') {
                $render .= '
                &lt;?=$' . $formName . '[\'' . $el->id . '\']->label(\'' . $el->id . '\')?>
                &lt;?=$' . $formName . '[\'' . $el->id . '\']->element?>
                
                &lt;br />
                ';
            } else {
                $render .= '
                &lt;?=$' . $formName . '[\'' . $el->id . '\']->element(\'' . $el->id . '\')?>
                
                &lt;br />
                ';
            }
        }

        $render .= '
        &lt;?=$' . $formName . '->renderEnd()?>
        </pre>
        ';

        return $render;
    }

    public function __toString()
    {
        return $this->render();
    }

    public function offsetSet($key, $value)
    {
        $this->elements[$key] = $value;
    }

    public function offsetGet($key)
    {
        if ($this->check($key)) {
            return $this->elements[$key];
        }
    }

    public function offsetUnset($key)
    {
        if ($this->check($key)) {
            unset($this->elements[$key]);
        }
    }

    public function offsetExists($key)
    {
        return $this->check($key);
    }

    private function check($key)
    {
        if (!array_key_exists($key, $this->elements)) {
            return false;
        }
        return true;
    }

}