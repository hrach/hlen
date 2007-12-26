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

class HDb extends HObject
{

    /** @var string */
    public static $db = null;

    /** @var array */
    private static $debugSql = array();

    /** @var boolean */
    private static $debug = false;

    /**
     * connect to db
     *
     * @param  array    $config
     * @param  boolean  $debug = false
     */
    public function connect($config = null, $debug = false)
    {
        if ( empty($config) && class_exists('HApplication', false) )
        {
            $config = HConfigure::read('Db.connections');
            $debug = (bool) HConfigure::read('Core.debug') > 1;
        }

        self::$debug = $debug;

        if (is_array($config)) {

            $serverName = $_SERVER['SERVER_NAME'];
            if (substr($serverName, 0, 4) === 'www.') {
                $serverName = substr($serverName, 4);
            }

            //$config[$serverName]['lazy'] = true;
            dibi::connect( $config[$serverName] );
        } else {
            //$config['lazy'] = true;
            dibi::connect( $config );
        }

        if (self::$debug) {
            dibi::addHandler('HDb::sqlHandler');
        }

        if ( class_exists('HApplication', false) ) {
            self::setModel( HRouter::$controller );
        }
    }

    /**
     * set the model to HApplication
     *
     * @param   string    $modelName
     * @return  boolean
     */
    static public function setModel($modelName)
    {
        if ( !class_exists('HApplication', false) ) {
            throw new LogicException("Tato funkce není povolena v tomto kontextu. Použijte HApplication.");
        }

        $modelName = HBasics::camelize($modelName);

        global $app_classes;
        if (!in_array($modelName, array_keys($app_classes))) {
            return false;
        }

        self::$db = $modelName;
        return true;
    }

    /**
     * sql handler - saving information of result of sql query 
     *
     * @param  object  $connection
     * @param  string  $event
     * @param  array   $arg
     * @return void
     */
    static public function sqlHandler($connection, $event, $arg)
    {
        if ($event === 'afterQuery') {
            self::$debugSql[] = array('query' => dibi::$sql,
                                      'time' => dibi::$elapsedTime,
                                      'affRows' => dibi::affectedRows());
        }
    }

    /**
     * print the debug sqls
     *
     * @param   void
     * @return  string
     */
    static public function afterRender()
    {
        if (self::$debug) {
            echo self::getDebug();
        }
    }


    /**
     *
     */
    public function getDebug()
    {
        $ret = "<table id=\"hlenSqlDebug\">\n";
        $ret .= "<tr><th>SQL Dotaz</th><th>Řádků</th><th>Čas</th></tr>\n";
        foreach (self::$debugSql as $query)
        {
            $ret .= "<tr><td>".$query['query']."</td>";
            $ret .= "<td>".$query['affRows']."</td>";
            $ret .= "<td>".sprintf('%0.3f', $query['time'] * 1000)."</td></tr>\n";
        }
        $ret .= "</table>\n";
        return $ret;
    }

}