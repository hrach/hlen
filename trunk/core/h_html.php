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


class HHtml
{

    /** @var string */
    public $base;

    /**
     * constructor
     *
     * @param void
     * @return void
     */
    public function __construct()
    {
        $this->base = HHttp::getBase();
    }

    /**
     * make a - link
     *
     * @param string $url
     * @param string $title
     * @return string
     */
    public function a($url, $title = "", $options = array())
    {
        $el = new HElement('a');

        foreach($options as $key => $val)
            $el[$key] = $val;

        $el['href'] = $this->url($url);
        $el->setContent(getVal($title, $options['href']));

        return $el->get();
    }

    /**
     * make url
     * 
     * @param string $url
     * @param boolean $absolute
     * @return string
     */
    public function url($url, $absolute = false)
    {
        if($absolute || strpos($url, 'http://') === false)
            $url = HHttp::getUrl() . HApplication::makeSystemUrl($url);

        return $url;
    }

}