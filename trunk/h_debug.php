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


class HDebug
{

    /**
     * dump var
     * @param mixed
     * @return void
     */
    public static function dump($var)
    {
        echo '<pre style="text-align: left;">';
        print_r($var);
        echo '</pre>';
    }

    /**
     * print debugging marks
     *
     * @param void
     * @return void
     */
    public function mark($var)
    {
        if ( (class_exists('HApplication', false) && HConfigure::read('Core.debug')) || !class_exists('HApplication', false))
        {
            echo '<span style="color: black;background: white;" class="debigging-marks">';
            echo 'Debug mark: <strong style="color: red;">'. $var .'</strong>';
            echo '</span><br/>';
        }
    }

}