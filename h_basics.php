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

class HBasics
{

    public static function camelize($word)
    {
        $replace = str_replace(" ", "", ucwords(str_replace("_", " ", $word)));
        return $replace;
    }
    
    public static function underscore($word)
    {
        $replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $word));
        return $replace;
    }
    
    
    public static function getVal()
    {
        foreach(func_get_args() as $var)
            if(!empty($var))
                return $var;
    }

}