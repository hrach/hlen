<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2007, Jan Skrasek
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/components/dibi.compact.php';


class HDb
{

    private static $debugSql = array();
    private static $debug = false;


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

    public static function afterRender()
    {
        if (self::$debug) {
            echo self::getDebug();
        }
    }

    public static function getDebug()
    {
        $ret = '<div id="sql-log"><table>'
             . '<tr><th>SQL Dotaz</th><th>Řádků</th><th>Čas</th></tr>';
        foreach (self::$debugSql as $query) {
            $ret .= '<tr><td>' . $query['query'] . '</td>'
                  . '<td style="width: 50px;">' . $query['affRows'] . '</td>'
                  . '<td style="width: 50px;">' . sprintf('%0.3f', $query['time'] * 1000) . '</td></tr>';
        }
        $ret .= '</table></div></div>';

        return $ret;
    }

}