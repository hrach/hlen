<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.3
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/components/dibi.compact.php';


class HDb
{

    private static $debugSql = array();
    private static $debug = false;


    public static function connect($config = 'Db.connections', $debug = 'Core.debug')
    {
        if (is_string($debug) && class_exists('HConfigure', false)) {
            $debug = HConfigure::read('Core.debug') > 2;
        } else {
            $debug = false;
        }

        if (is_string($config) && class_exists('HConfigure', false)) {
            $config = HConfigure::read('Db.connections');
        } else {
            $config = null;
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
        $ret = '<table style="position: fixed;bottom: 0;left: 0;border: 1px solid #444;border-collapse: collapse;font-size: 12px;">'
             . '<tr><th style="background: #777;border: 1px solid #444;color: white;">SQL Dotaz</th>'
             . '<th style="background: #777;border: 1px solid #444;color: white;">Řádků</th>'
             . '<th style="background: #777;border: 1px solid #444;color: white;">Čas</th></tr>';
        foreach (self::$debugSql as $query) {
            $ret .= '<tr><td style="backgroud: #fff;border: 1px solid #444;">' . $query['query'] . '</td>'
                  . '<td style="backgroud-color: #fff;width: 50px;border: 1px solid #444;">' . $query['affRows'] . '</td>'
                  . '<td style="backgroud:  #fff;width: 50px;border: 1px solid #444;">' . sprintf('%0.3f', $query['time'] * 1000) . '</td></tr>';
        }
        $ret .= '</table></div></div>';

        return $ret;
    }

}