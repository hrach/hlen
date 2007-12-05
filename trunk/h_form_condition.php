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
     * @param object $from
     * @return boolean
     */
    public function validate($value, $form)
    {
        if ( !$this->validateField( $this->rule, $value, $this->arg ) ) {
            return true;
        }

        foreach ($this->rules as $rule)
        {              
            if ( is_object($rule['arg']) ) {
                $arg = $form->submitedData[ $rule['arg']->getId() ];
            } else {
                $arg = $rule['arg'];
            }

            if( !$this->validateField( $rule['rule'], $value, $arg ) ) {
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
     * @param string $arg = null
     * @return boolean
     */
    private function validateField($rule, $value, $arg = null)
    {
        switch($rule)
        {
            case HForm::EQUAL:
                if ($value != $arg) {
                    return false;
                }
            break;
            case HForm::FILLED:
                HDebug::dump($value);
                if (empty($value)) { //$value !== $arg && 
                    return false;
                }
            break;
            case HForm::EMAIL:
                if ( !preg_match('/^[^@]+@[^@]+\.[a-z]{2,6}$/i', $value) ) {
                    return false;
                }
            break;
            case HForm::URL:
                if ( !preg_match('/^.+\.[a-z]{2,6}(\\/.*)?$/i', $value) ) {
                    return false;
                }
            break;
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