<?php

/**
 * HLEN FRAMEWORK
 *
 * @author     Jan Skrasek <skrasek.jan@gmail.com>
 * @copyright  Copyright (c) 2008, Jan Skrasek
 * @version    0.4
 * @package    Hlen
 */


class HAutoLoad
{

    public static $cacheFile;
    public static $classList = array();
    public static $coreFiles = array('hdb', 'hcookie', 'hform','hsession', 'hhtml', 'hcontroller', 'hbasics');


    /*
     * Zaregistruje auto-load
     * 
     * @return	void
     */
    public static function registerAutoload()
    {
        self::$cacheFile = HConfigure::read('Autoload.cache.file', APP . 'temp/classes.cache.txt');
        self::createClassList();

        spl_autoload_register(array('HAutoLoad', 'autoLoadHandler'));
    }

    /*
     * Handler pro auto-load
     * 
     * @param	string	jmeno tridy
     * @return	void
     */
    public static function autoLoadHandler($className)
    {
        $className = strtolower($className);

        if (isset(self::$classList[$className])) {
            require_once self::$classList[$className];
        } elseif (in_array($className, self::$coreFiles)) {
            require_once CORE . $className . '.php';
        }
    }

    /*
     * Cachuje pole $class => $cesta
     * 
     * @return	void
     */
    private static function createClassList()
    {
        if (!file_exists(APP)) {
            return array();
        }

        if (file_exists(self::$cacheFile)) {
            self::$classList = unserialize(file_get_contents(self::$cacheFile));
        } else {
            self::findClasses();
            if (file_exists(dirname(self::$cacheFile))) {
            	file_put_contents(self::$cacheFile, serialize(self::$classList));	
            }
        }
    }

    /*
     * Vytvori pole $class => $cesta
     * 
     * @retrun	void
     */
    private static function findClasses()
    {
        $files = self::getFiles(APP);

        foreach ($files as $file) {
            self::getClasses($file);
        }
    }

    /*
     * Vrato soubory prislusneho adresare
     * 
     * @param	string	cesta prohledavaneho adresare
     * @return	array
     */
    private static function getFiles($dir)
    {
        $folder = new DirectoryIterator($dir);
        $files  = array();

        foreach ($folder as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isDir()) {
                $files   = array_merge($files,  self::getFiles($dir . '/' . $file->getFilename()));
            } elseif (preg_match('/.php$/', $file->getFilename())) {
                $files[] = $dir . '/' . $file->getFilename();
            }
        }

        return $files;
    }

    /*
     * Najde v souboru vsechny tridy a ulozi cesty k nim
     * 
     * @param	string	$file
     * @return	void
     */
    private static function getClasses($file) {
        $catch = false;

        foreach (token_get_all(file_get_contents($file)) as $token) {
            if (is_array($token)) {
                if ($token[0] == T_CLASS || $token[0] == T_INTERFACE) {
                    $catch = true;
                } elseif ($token[0] == T_STRING && $catch) {
                    self::$classList[strtolower($token[1])] = $file;
                    $catch = false;
                }
            }
        }
    }

}