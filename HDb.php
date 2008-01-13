<?php

/**
 * Hlen Framework
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/components/dibi.compact.php';


/**
 * Model MVC aplikace 
 *
 * Trida napojuje Dibi na Hlen, sama dabuguje dotazy, a nasledne je i vypise
 * @package   Hlen
 * @author    Jan Skrasek
 * @version   0.1.0
 */
class HDb
{

    /** @var array */
    private static $debugSql = array();
    /** @var boolean */
    private static $debug = false;


    /**
     * Pripoji se k db
     *
     * @param array    $config
     * @param boolean  $debug = false
     */
    public static function connect($config = null, $debug = false)
    {
        if (empty($config) && class_exists('HApplication', false)) {
            $config = HConfigure::read('Db.connections');
            $debug = HConfigure::read('Core.debug') > 1;
        }

        self::$debug = $debug;

        if (is_array($config)) {
            $serverName = $_SERVER['SERVER_NAME'];
            if (substr($serverName, 0, 4) === 'www.') {
                $serverName = substr($serverName, 4);
            }

            dibi::connect($config[$serverName]);
        } else {
            dibi::connect($config);
        }

        if (self::$debug) {
            dibi::addHandler(array('HDb', 'sqlHandler'));
        }
    }

    /**
     * Sql handler - loguje sql dotazy
     *
     * @param object  $connection
     * @param string  $event
     * @param array   $arg
     */
    public static function sqlHandler($connection, $event, $arg)
    {
        if ($event === 'afterQuery') {
            self::$debugSql[] = array(
                'query' => dibi::$sql,
                'time' => dibi::$elapsedTime,
                'affRows' => dibi::affectedRows(),
            );
        }
    }

    /**
     * Vypise sql log
     */
    static public function afterRender()
    {
        if (self::$debug) {
            echo self::getDebug();
        }
    }


    /**
     * Vrati zformatovany sql log
     */
    public function getDebug()
    {
        $ret = '<table id="sql-log">'
             . '<tr><th>SQL Dotaz</th><th>Řádků</th><th>Čas</th></tr>';
        foreach (self::$debugSql as $query) {
            $ret .= '<tr><td>' . $query['query'] . '</td>'
                  . '<td>' . $query['affRows'] . '</td>'
                  . '<td>' . sprintf('%0.3f', $query['time'] * 1000) . '</td></tr>';
        }
        $ret .= '</table>';

        return $ret;
    }

}