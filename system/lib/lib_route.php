<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * URL处理类
 */
final class Route
{
    public $url_query;
    public $url_type;
    public $route_url = array();

    public function __construct()
    {
        $this->url_query = parse_url($_SERVER['REQUEST_URI']);
    }

    /**
     * 设置URL类型
     * @access      public
     */
    public function setUrlType($url_type = 1)
    {
        if ( $url_type > 0 && $url_type < 3 )
        {
            $this->url_type = $url_type;
        } else {
            trigger_error("指定的URL模式不存在！");
        }
    }

    /**
     * 获取数组形式的URL
     * @access      public
     */
    public function getUrlArray()
    {
        $this->makeUrl();
        return $this->route_url;
    }

    /**
     *  url构造
     * @access      public
     */
    public function makeUrl()
    {
        switch ( $this->url_type )
        {
            case 1:
                $this->querytToArray();
                break;
            case 2:
                $this->pathinfoToArray();
                break;
        }
    }

    /**
     * 将query形式的URL转化成数组
     * @access      public
     */
    public function querytToArray()
    {
        $arr = empty( $this->url_query['query'] ) ? array() : explode('&', $this->url_query['query']);
        $array = $tmp = array();
        if ( count($arr) > 0 )
        {
            foreach ($arr as $item)
            {
                $tmp = explode('=', $item);
                $array[$tmp[0]] = $tmp[1];
            }
            if ( isset($array['m']) )
            {
                $this->route_url['app'] = $array['m'];
                unset($array['m']);
            }
            if ( isset($array['c']) )
            {
                $this->route_url['controller'] = $array['c'];
                unset($array['c']);
            }
            if ( isset($array['a']) )
            {
                $this->route_url['action'] = $array['a'];
                unset($array['a']);
            }
            if ( count($array) > 0 )
            {
                $this->route_url['params'] = $array;
            }
        } else {
            $this->route_url = array();
        }
    }

    /**
     * 将PATH_INFO的URL形式转化为数组
     * @access      public
     */
    public function pathinfoToArray()
    {
        if ( isset($_SERVER['PATH_INFO']) )
        {
            $pathinfo =  explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if ( is_array($pathinfo) && ! empty($pathinfo) )
            {
                if ( count($pathinfo) === 1 )
                {
                    $this->route_url['controller'] = $pathinfo[0];
                } else if ( count($pathinfo) === 2 ) {
                    $this->route_url['controller'] = $pathinfo[0];
                    $this->route_url['action'] = $pathinfo[1];
                } else if ( count($pathinfo) === 3 ) {
                    $this->route_url['app'] = $pathinfo[0];
                    $this->route_url['controller'] = $pathinfo[1];
                    $this->route_url['action'] = $pathinfo[2];
                } else {
                    $this->route_url['app'] = $pathinfo[0];
                    $this->route_url['controller'] = $pathinfo[1];
                    $this->route_url['action'] = $pathinfo[2];
                    unset($pathinfo[0]);
                    unset($pathinfo[1]);
                    unset($pathinfo[2]);
                    $this->route_url['params'] = $pathinfo;
                }
            } else {
                $this->route_url = array();
            }
        } else {
            $this->route_url = array();
        }

    }

}