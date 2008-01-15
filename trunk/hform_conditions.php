<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */
   

class HFormCondition
{

    private $rule;
    private $form;
    private $el;
    private $arg;
    private $rules = array();


    public function __construct($rule, $form, $el, $arg = null)
    {
        $this->rule = $rule;
        $this->form = $form;
        $this->el = $el;
        $this->arg = $arg;             
    }

    public function isValid($value)
    {
        if (!empty($this->rule))
        {
            if (is_object($this->arg)) {
                $this->arg = $this->form->data[$this->el->id];
            }

            if (!$this->isValueValid($this->rule, $value, $this->arg, $this->el->getEmptyValue())) {
                return true;
            }
        }

        foreach ($this->rules as $rule) {
            if (is_object($rule['arg'])) {
                $arg = $this->form->data[$rule['arg']->id];
            } else {
                $arg = $rule['arg'];
            }
            
            if (!$this->isValueValid($rule['rule'], $value, $arg, $emptyValue)) {
                $this->form->addError($rule['message']);
                return false;
            }
        }

        return true;
    }

    public function addRule($rule, $message, $arg = null)
    {
        $this->rules[] = array(
            'rule'    => $rule,
            'message' => $message,
            'arg'     => $arg,
        );
    }

    private function isValueValid($rule, $value, $arg, $emptyValue)
    {
        if ($value == $emptyValue) {
            $value = null;
        }

        switch ($rule) {
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

}