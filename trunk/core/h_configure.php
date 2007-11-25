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


final class HConfigure
{

    /** @var array */
    static private $config = array();

    /**
     * set config
     * @param string
     * @param mixed
     * @return void
     */
    public static function write($var, $val)
    {
        HConfigure::$config[$var] = $val;
    }

    /**
     * read config
     * @param string
     * @return mixed
     */
    public static function read($var)
    {
        if(isset(HConfigure::$config[$var]))
            return HConfigure::$config[$var];
        else
            return false;
    }

}