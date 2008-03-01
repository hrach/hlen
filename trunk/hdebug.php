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

    public static function debugErrors()
    {
        if (function_exists('ini_set')) {
            ini_set('show_errors', true);
            ini_set('error_reporting', E_ALL);
        }
    }

    public static function logErrors()
    {
        if (function_exists('ini_set')) {
            ini_set('display_errors', false);
            ini_set('error_reporting', E_ERROR | E_WARNING | E_PARSE);
            ini_set('log_errors', true);
            ini_set('error_log', HConfigure::read('Core.debug.file', APP . 'temp/errors.log'));
        }
    }

    public static function dump($var, $escapeHtml = true)
    {
        echo '<pre style="text-align: left;">';
        if ($escapeHtml) {
            echo htmlspecialchars(print_r($var, true));
        } else {
            print_r($var);
        }
        echo '</pre>';
    }

    public function mark($var)
    {
        echo '<span style="color: black;background: white;" class="debigging-marks">';
        echo 'Debug mark: <strong style="color: red;">' . $var . '</strong>';
        echo '</span><br/>';
    }

}