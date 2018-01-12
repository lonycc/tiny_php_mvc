<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 核心模型类
 */
class Model
{
    protected $db = null;

    /**
     * 构造函数，初始化全局数据库连接实例
     */
    final public function __construct()
    {
        $this->db = pdomysql::getInstance();
    }

    /**
     * 加载类库
     * @param string $lib  类库名称
     * @param Bool  $auto  如果为TRUE则加载系统核心类库，否则加载用户自定义类库
     * @return object
     */
    final protected function load($lib, $auto = TRUE)
    {
        if ( empty($lib) )
        {
            trigger_error('加载类库名不能为空');
        } else if ($auto === TRUE) {
                        return Application::$_lib[$lib];
        } else if ($auto === FALSE) {
            return  Application::newLib($lib);
        }
    }

    /**
     * 加载系统配置,默认为系统配置 $CONFIG['system'][$config]
     * @access      final   protected
     * @param       string  $config 配置名
     */
    final protected function config($config)
    {
        return Application::$_config[$config];
    }

}