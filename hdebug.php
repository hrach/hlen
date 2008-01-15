<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


class HDebug
{

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
        if ((class_exists('HApplication', false) && HConfigure::read('Core.debug') > 1)
         || !class_exists('HApplication', false)) {
            echo '<span style="color: black;background: white;" class="debigging-marks">';
            echo 'Debug mark: <strong style="color: red;">' . $var . '</strong>';
            echo '</span><br/>';
        }
    }

}