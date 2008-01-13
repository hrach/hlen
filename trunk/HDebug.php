<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */


/**
 * Debugger
 *
 * Jednoduchy debugger
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HDebug
{

    /**
     * Vypise strukturu a obsah promenne
     *
     * @param mixed   $var
     * @param boolean $escapeHtml = true
     */
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

    /**
     * Vypise debuguovaci znacku
     *
     * Pouze pri ladicim rezimu
     * @param string $var
     */
    public function mark($var)
    {
        if ((class_exists('HApplication', false) && HConfigure::read('Core.debug')) || !class_exists('HApplication', false)) {
            echo '<span style="color: black;background: white;" class="debigging-marks">';
            echo 'Debug mark: <strong style="color: red;">' . $var . '</strong>';
            echo '</span><br/>';
        }
    }

}