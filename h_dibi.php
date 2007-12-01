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

require_once dirname(__FILE__).'/components/dibi.compact.php';

class HDibi extends HObject
{

    /** @var array */
    static private $debugSql = array();

    /** @var boolean */
    static private $debug = false;

    /**
     * return model object
     *
     * @param string $modelName
     * @return object
     */
    static public function getModel($modelName)
    {
        if ( HBasics::load(APP."models/".HBasics::underscore($modelName).".php")) {
            return new $modelName;
        }
        return null;
    }

    /**
     * connect to db
     *
     * @param array $config
     * @param boolean $debug = false
     */
    public function connect($config, $debug = false)
    {
        self::$debug = $debug;

        if (is_array($config))
        {
            $serverName = $_SERVER['SERVER_NAME'];
            if(substr($serverName, 0, 4) === 'www.')
                $serverName = substr($serverName, 5);

            dibi::connect( $config[$serverName] );
        }
        else
        {
            dibi::connect( $config );
        }

        if (self::$debug) {
            dibi::addHandler('HDibi::sqlHandler');
        }
    }

    /**
     * sql handler - saving information of result of sql query 
     *
     * @param object? $connection
     * @param string $event
     * @param array? $arg
     */
    static public function sqlHandler($connection, $event, $arg)
    {
        if ($event === 'afterQuery') {
            HDibi::$debugSql[] = array('query' => dibi::$sql, 'time' => dibi::$elapsedTime, 'affRows' => dibi::affectedRows());
        }
    }

    /**
     *
     */
    static public function afterRender()
    {
        if(self::$debug)
            echo self::getDebug();
    }


    /**
     *
     */
    public function getDebug()
    {
        $ret = "<table id=\"hlenSqlDebug\">\n";
        $ret .= "<tr><th>SQL Dotaz</th><th>Řádků</th><th>Čas</th></tr>\n";
        foreach ($this->debugSql as $query)
        {
            $ret .= "<tr><td>".$query['query']."</td>";
            $ret .= "<td>".$query['affRows']."</td>";
            $ret .= "<td>".sprintf('%0.3f', $query['time'] * 1000)."</td></tr>\n";
        }
        $ret .= "</table>\n";
        return $ret;
    }

}