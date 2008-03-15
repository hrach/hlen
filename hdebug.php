<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */


class HDebug
{

	/*
	 * Vypise lidsky-citelny obsah promenne
	 * 
	 * @param	mixed	promenna pro vypis
	 * @param	boolean	vratit misto vypsani
	 * @param	boolean	escapovat text
	 * @return	void
	 */
    public static function dump($var, $return = false, $escapeHtml = true)
    {
        if ($escapeHtml) {
            $content = htmlspecialchars(print_r($var, true));
        } else {
            $content = print_r($var, true);
        }

        if ($return) {
        	return '<pre style="text-align: left;">' . $content . '</pre>';        	
        } else {
        	echo '<pre style="text-align: left;">' . $content . '</pre>';        	
        }
    }

    /*
     * Zapne zachyceni neodchycenych vyjimek
     * 
     * @param	boolen	debug rezim
     * @return	void 
     */
    public static function enableExceptions($debug = false)
    {
    	if ($debug) {
			set_exception_handler(array('HDebug', 'exceptionHandler'));
    	} else {
	    	set_exception_handler(array('HDebug', 'exceptionHandlerApp'));
    	}
    }
    
    /*
     * Zachyti neodchycene vyjimky a zobrazi podrobny vypis chyby
     * 
     * @param	Exception	nezachycena vyjimka
     * @return	void 
     */
    public static function exceptionHandler(Exception $exception)
    {
    	require_once dirname(__FILE__) . '/hdebug_template.phtml';
    }
    
	/*
     * Zachyti neodchycene vyjimky a zobrazi podrobny vypis chyby v ramci aplikace
     * Render pro koncove uzivatele
     * 
     * @param	Exception	nezachycena vyjimka
     * @return	void 
     */
    public static function exceptionHandlerApp(Exception $exception)
    {
    	HApplication::$error = true;
		HApplication::$controller = new Controller;
        HApplication::$controller->view->view('500');
        HApplication::$controller->view->render();
    }
    
    /*
     * Zapne vypisovani chyb
     * 
     * @return	void
     */
    public static function enableErrors()
    {
        if (function_exists('ini_set')) {
            ini_set('show_errors', true);
            ini_set('error_reporting', E_ALL);
        }
    }

    /*
     * Zapne lgovani chyb do souboru
     * 
     * @return	void
     */
    public static function logErrors()
    {
        if (function_exists('ini_set')) {
            ini_set('display_errors', false);
            ini_set('error_reporting', E_ERROR | E_WARNING | E_PARSE);
            ini_set('log_errors', true);
            ini_set('error_log', HConfigure::read('Core.debug.file', APP . 'temp/errors.log'));
        }
    }

}