<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Trida pro praci se Session
 *
 * Efektne uklada a cte session promenne
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HSession
{

	function __construct()
	{
		$this->init();
		$this->start();
	}

	private function init()
	{
		if (function_exists('ini_set')) {
			ini_set('session.use_cookies', 1);
			ini_set('session.name', Configure::read('Session.cookie'));
			ini_set('session.cookie_lifetime', Configure::read('Session.lifeTime'));
			if(Configure::read('Session.ownTempDir') === true)
				ini_set('session.save_path', Configure::read('Session.tempDir'));
		}
	}

	private function start()
	{
		session_start();
	}

	function read($var)
	{
		return $_SESSION[$var];
	}

	public function write($var, $val)
	{
		$_SESSION[$var] = $val;
	}

	public function delete($var)
	{
		unset($_SESSION[$var]);
	}

	public function destroy()
	{
		session_destroy();
	}

}