<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.5
 * @package    Hlen
 */

require_once dirname(__FILE__) . '/components/dibi.compact.php';


class HDb
{

    private static $debugSql = array();
    private static $debug = false;


    /*
     * Pripoji se k databazi
     * Pokud neni predan jako paramatr pole pripojeni, je nacteno z HConfigure::read('Db.connections')
     * Format pole:
     * 			array(
     * 				'driver' => 'mysql',
     * 				// e.g.
     * 			)
     * Pokud chcete vyuzit multi-pripojeni, vyzijte nasledujicho tvaru pole:
     * 			array(
     * 				'server.name.com' => array(
     * 						'driver' => 'mysql',
     * 						// e.g.
     * 					)
     * 			)
     * 
     * @param	array	pole s propojenim(i)
     * @return	void
     */
    public static function connect(array $config = null)
    {
        self::$debug = HConfigure::read('Core.debug', 0) > 1;
        
        if ($config === null) {
            $config = HConfigure::read('Db.connections');
        }

        if (is_array($config)) {
            $serverName = $_SERVER['SERVER_NAME'];
            if (HConfigure::read('Db.trim.www', true) && substr($serverName, 0, 4) === 'www.') {
                $serverName = substr($serverName, 4);
            }

            if (isset($config[$serverName])) {
            	dibi::connect($config[$serverName]);
            } else {
	            dibi::connect();
            }
        } else {
            dibi::connect($config);
        }

        if (self::$debug) {
            dibi::addHandler(array('HDb', 'sqlHandler'));
        }
    }

    /*
     * Handler pro debug sql
     * 
     * @param	DibiConnection	pripojeni
     * @param	DibiEvent		zprava
     * @param	mixed			argument
     * @return	void
     */
    public static function sqlHandler($connection, $event, $arg)
    {
        if ($event === 'afterQuery') {
            self::$debugSql[] = array(
                'query'		=> dibi::$sql,
                'time'		=> dibi::$elapsedTime,
                'affRows'	=> dibi::affectedRows(),
            );
        }
    }

    /*
     * Vypise sql debug
     * Jedna se o tabulku, bude umistena vlevo nahore. Specialne nasriptovana.
     * 
     * @return	string
     */
    public static function getDebug()
    {
        $ret = '<div id="hlen-sql-log" style="position:fixed;top:0;left:0;text-align:left;">'
             . '<script type="text/javascript">//<![CDATA[
                function hlen_sql_table() { table=document.getElementById(\'hlen-sql-log-table\'); table.style.display = (table.style.display==\'block\') ? \'none\' : \'block\'; }
                //]]>
                </script>'
             . '<a style="border:1px solid #888;background: white;" href="javascript:hlen_sql_table()">SQL log</a><br />'
             . '<table id="hlen-sql-log-table" style="display:none;background:white;border:1px solid #444;border-collapse:collapse;font-size: 12px;">'
             . '<tr><th style="background: #777;border: 1px solid #444;color: white;">SQL Dotaz</th>'
             . '<th style="background: #777;border: 1px solid #444;color: white;">Řádků</th>'
             . '<th style="background: #777;border: 1px solid #444;color: white;">Čas</th></tr>';
        foreach (self::$debugSql as $query) {
            $ret .= '<tr><td style="backgroud: #fff;border: 1px solid #444;">' . $query['query'] . '</td>'
                  . '<td style="backgroud-color: #fff;width: 50px;border: 1px solid #444;">' . $query['affRows'] . '</td>'
                  . '<td style="backgroud:  #fff;width: 50px;border: 1px solid #444;">' . sprintf('%0.3f', $query['time'] * 1000) . '</td></tr>';
        }
        $ret .= '</table></div>';

        return $ret;
    }

}