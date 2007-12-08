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
            case HForm::EQUAL:  return $value == $arg; break;
            case HForm::FILLED: return !empty($value); break;
            case HForm::EMAIL:  return preg_match('/^[^@]+@[^@]+\.[a-z]{2,6}$/i', $value); break;
            case HForm::URL:    return preg_match('/^.+\.[a-z]{2,6}(\\/.*)?$/i', $value); break;

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