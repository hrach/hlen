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
     * @param mixed
     */
    public static function dump($var)
    {
        echo '<pre style="text-align: left;">';
        print_r($var);
        echo '</pre>';
    }

    /**
     * Vypise debuguovaci znacku
     *
     * Pouze pri ladicim rezimu
     */
    public function mark($var)
    {
        if ( (class_exists('HApplication', false) && HConfigure::read('Core.debug'))
             || !class_exists('HApplication', false)
        ) {
            echo '<span style="color: black;background: white;" class="debigging-marks">';
            echo 'Debug mark: <strong style="color: red;">' . $var . '</strong>';
            echo '</span><br/>';
        }
    }

}