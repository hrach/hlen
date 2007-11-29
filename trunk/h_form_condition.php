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


class HFormCondition
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
	 * @param mixed $arg
	 * @return void
	 */
	public function __construct($rule, $arg = null)
	{
		$this->rule = $rule;
		$this->arg = $arg;
	}

	/**
	 * can validate
	 *
	 * @param array $data
	 * @param object $from
	 * @return boolean
	 */
	public function canValidate($data, $form)
	{
		switch($this->rule)
		{
			case HForm::FILLED:
				if(!empty($data))
					return true;
			break;
			case HForm::EQUAL:
				if(is_object($this->arg))
					$arg = $form->submitedData[$this->arg->getId()];
				else
					$arg = $this->arg;
				if($data == $arg)
					return true;
			break;
			case 0:
				return true;
			break;

		}
		return false;
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

	/**
	 * get rules
	 *
	 * @param void
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
	}
}