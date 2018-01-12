<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	$CONFIG['system']['db'] = array(
		'dsn' 			=> 'mysql:host=localhost;dbname=kechuang;port=3306',
		'db_user'		=> 'root',
		'db_pass'		=> '123456',
		'db_conn'		=> false,	//是否长连接
		'table_prefix'		=> 'app_',
		'db_charset'	=> 'utf8',
	);

	$CONFIG['system']['lib'] = array(
		'prefix'			=> 'my'  //自定义类库的文件前缀
	);

	$CONFIG['system']['route'] = array(
		'default_controller'	=> 'test', //系统默认控制器
		'default_action'		=> 'index', //系统默认方法
		'url_type'		=> 2,	/* 1为普通模式,index.php?m=module&c=controller&a=action&id=2
	                                     			 * 2为pathinfo模式,index.php/module/controller/action/id/2
	                                    			 */
	);

	$CONFIG['system']['cache'] = array(
		'cache_dir'		=> 'cache',  //缓存路径
		'cache_prefix'		=> 'cache_', //缓存文件名前缀
		'cache_time'		=> 1800,	 //缓存时间默认1800s
		'cache_mode'		=> 2,		 //1为serialize,2为可执行文件
	);