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


class HLink
{

    /**
     * make a - link
     *
     * @param string $url
     * @param string $title
     * @return string
     */
    static public function a($url, $title = "", $options = array())
    {
        $el = new HHtml('a');

        foreach($options as $key => $val)
            $el[$key] = $val;

        $el['href'] = self::url($url);
        $el->setContent(HBasics::getVal($title, $options['href']));

        return $el->get();
    }

    /**
     * make url
     *
     * @param string $url
     * @param boolean $absolute
     * @return string
     */
    static public function url($url, $absolute = false)
    {
        if($absolute || strpos($url, 'http://') === false)
            $url = HHttp::getUrl() . HApplication::systemUrl($url);

        return $url;
    }

}