<?php

class SystemController extends Controller
{

	public function error($code, $message)
	{
		$arg = func_get_args();
		$this->set('arg', $arg);

		if(HConfigure::read('Core.debug') < 2)
			$this->view = '404';
		else
			$this->view = $code;
	}

}