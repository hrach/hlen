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


abstract class HObject
{

    final public function getClass()
    {
        return get_class($this);
    }

    protected function &__get($var)
    {
        if ($var === '') {
            throw new LogicException("Nelze číst bezejmennou propetry.");
        }

        $class = $this->getClass();
        if (self::hasAccessor($class, 'get'.$var)) {
            $val = $this->{'get'.$var}();
            return $val;
        } else {
            throw new LogicException("Nelze číst neexistující propetry $class::\$$var. ");
        }
    }

    protected function __set($var, $val)
    {
        if ($name === '') {
            throw new LogicException("Nelze číst bezejmennou propetry.");
        }

        $class = $this->getClass();
        if (self::hasAccessor($class, 'get'.$var)) {
            if (self::hasAccessor($class, 'set'.$var)) {
                $class->{'set'.$var}();
            } else {
                throw new LogicException("Nelze zapisovat do read-only propetry $class::\$$var. ");
            }
        } else {
            throw new LogicException("Nelze číst neexistující propetry $class::\$$var. ");
        }
    }

    protected function __isset($var)
    {
        return $name !== '' && self::hasAccessor($this->getClass(), 'get' . $name);
    }

    protected function __unset($var)
    {
        $class = $this->getClass();
        throw new LogicException("Nelze číst neexistující propetry $class::\$$var. ");
    }

    private static function hasAccessor($class, $method)
    {
        static $cache;
        if (!isset($cache[$class])) {
            $cache[$class] = array_flip(get_class_methods($class));
        }

        $method[3] = $method[3] & "\xDF";
        return isset($cache[$class][$method]);
    }

}