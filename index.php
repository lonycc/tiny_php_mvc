<?php
    define('BASEPATH', dirname(__FILE__));  //定义基本路径常量
    require dirname(__FILE__).'/system/app.php'; //引入系统的驱动类
    require dirname(__FILE__).'/config/config.php';  //引入配置文件
    Application::run($CONFIG);  //运行一个应用实例