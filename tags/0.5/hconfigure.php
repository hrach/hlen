<?php

/**
 * HLEN FRAMEWORK
 *
 * @author      Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright   Copyright (c) 2008, Jan Skrasek
 * @version     0.5 $WCREV$
 * @package     Hlen
 */


/**
 * Trida HConfigure ma na starosti sluzby kolem konfigurace vasi aplikace
 */
class HConfigure
{

    private static $config = array();


    /**
     * Zapise konfiguraci
     *
     * @param   mixed   jmeno konfiguracni direktivy
     * @param   mixed   hodnota
     * @return  void
     */
    public static function write($var, $val = null)
    {
        if (!empty($var) && !empty($val)) {
            self::$config[$var] = $val;
        }
    }

    /**
     * Vybere se jeho odpovidaji klic (nazev domeny) z $configure
     * Hodnota klice (pole) je dale zpracovano jako klasicka konfigurace
     *
     * @param   array   konfiguracni pole
     * @param   bool    odstranit z nazvu domeny www
     * @return  void
     */
    public static function writeMulti(array $configure, $trimWww = false)
    {
        $serverName = $_SERVER['SERVER_NAME'];

        // (bool) $val - odstranit www. u jmena serveru
        if ($trimWww === true && substr($serverName, 0, 4) === 'www.') {
            $serverName = substr($serverName, 4);
        }

        foreach ($configure[$serverName] as $key => $val) {
            self::$config[$key] = $val;
        }
    }

    /**
     * Parsuje konfiguraci v jazyce YAML
     * Popis konfigurace naleznete na http://hlen.programujte.com/manual/show/api-hconfigure
     *
     * @param   string  jmeno konfiguracniho souboru
     * @return  bool
     */
    public static function loadYaml($fileName)
    {
        static $loaded = false;

        if (!file_exists($fileName)) {
            return false;
        } else {
            if (!$loaded) {
                require dirname(__FILE__) . '/components/spyc.php';
            }

            $data = Spyc::YAMLLoad($fileName);
            foreach ($data as $key => $val) {
                if ($key == 'multi' && is_array($val)) {
                    self::writeMulti($val);
                } elseif ($key == 'multi:trim' && is_array($val)) {
                    self::writeMulti($val, true);
                } else {
                    self::write($key, $val);
                }
            }

            return true;
        }
    }

    /**
     * Precte konfiguraci
     * Pokud neni direktiva dostupna (nebyla jeste nastavena), vrati metoda druhy argument
     *
     * @param   string  jmeno direktivy
     * @param   mixed   vyhozi hodnota
     * @return  mixed
     */
    public static function read($var, $default = false)
    {
        if (isset(self::$config[$var])) {
            return self::$config[$var];
        } else {
            return $default;
        }
    }

}