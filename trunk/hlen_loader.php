<?php

if(version_compare(PHP_VERSION, '5.0.0') < 0)
    Die('HLEN need PHP in version 5 and higher!');

define('HLEN_VERSION', '0.1.0');
define('HLEN_CORE', dirname(__FILE__).'/');
define('COMPONENTS', dirname(__FILE__).'/core/components/');
define('APP', dirname($_SERVER['SCRIPT_FILENAME']).'/application/');

define('MINUTE', 60);
define('HOUR', 60*MINUTE);
define('DAY', 24*HOUR);
define('WEEK', 7*DAY);
define('MOON', 30*DAY);

if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST, &$_FILES);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

function camelize($word)
{
    $replace = str_replace(" ", "", ucwords(str_replace("_", " ", $word)));
    return $replace;
}

function underscore($word)
{
    $replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $word));
    return $replace;
}

function __autoload($class)
{
    $file = underscore($class);
    require_once HLEN_CORE."core/$file.php";
}

/**
 * try load a file
 * @param string
 * @param boolean
 * @return boolean
 */
function load($fileName, $once = true)
{
    if(file_exists($fileName))
    {
        if($once)
            require_once($fileName);
        else
            require($fileName);
        return true;
    }
    return false;
}

function getVal()
{
	foreach(func_get_args() as $var)
		if(!empty($var))
			return $var;
}