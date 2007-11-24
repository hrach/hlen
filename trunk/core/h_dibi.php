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


class HDibi {

    /** @var array */
    private $debugSql = array();

    /**
     * constructor
     *
     * @param void
     * @return void
     */
    public function __construct()
    {
        load(HLEN_CORE.'core/components/dibi.compact.php');
    }

    public function afterRender()
    {
        if(HConfigure::read('Core.debug') > 1)
            echo $this->getDebug();
    }

    public function connect($config)
    {
        try {
            dibi::connect(array(
                'driver'    => $config['driver'],
                'host'      => $config['server'],
                'username'  => $config['user'],
                'password'  => $config['password'],
                'database'  => $config['database'],
                'charset'   => $config['encoding'],
            ));
        } catch (DibiException $e) {
            HDebug::dump($e);
        }

        if(HConfigure::read('Core.debug') > 1)
            dibi::addHandler(array(get_class($this), 'sqlHandler'));
    }

    public function sqlHandler($connection, $event, $arg)
    {
        if($event === 'afterQuery')
            HApplication::$controller->dibi->debugSql[] = array('query' => dibi::$sql,
                                                                'time' => dibi::$elapsedTime,
                                                                'affRows' => dibi::affectedRows()
                                                                );
    }

    public function getDebug()
    {
        $ret = "<table id=\"hlenSqlDebug\">\n";
        $ret .= "<tr><th>SQL Dotaz</th><th>Řádků</th><th>Čas</th></tr>\n";
        foreach($this->debugSql as $query) {
            $ret .= "<tr><td>".$query['query']."</td>";
            $ret .= "<td>".$query['affRows']."</td>";
            $ret .= "<td>".sprintf('%0.3f', $query['time'] * 1000)."</td></tr>\n";
        }
        $ret .= "</table>\n";
        return $ret;
    }

}