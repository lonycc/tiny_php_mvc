## 自己动手写个基于php的mvc框架

> PHP框架众多，著名的有Yii、Laravel、Zend Framework、Symfony、Phalcon等，关于各种框架孰优孰劣，混迹PHP圈子的程序员们各有看法。在开发过程中，选择合适的框架固然重要，理解框架的原理才能万变不离其宗。现如今流行的各种框架，基本都是MVC架构，实现起来也大同小异。为了深度理解MVC架构原理，我们有必要自己动手去写一个。

### 1、目录结构
```
app
|-controller    存放控制器文件
|-model     存放模型文件
|-view      存放视图文件
|-lib       存放自定义类库
|-config    存放配置文件
|--config.php   应用配置文件
|-system    系统目录
|--app.php  系统驱动文件
|--core     系统核心目录
|---model.php  核心模型文件
|---controller.php   核心控制器文件
|--lib      系统核心类库
|---lib_route.php   路由解析类库
|---lib_template.php  模板处理类库
|---lib_pdomysql.php  pdo mysql 操作类库
|---lib_cache.php    缓存操作类库
|---lib_crypt.php    加密解密类库
|---lib_thumbnail.php  图片处理类库
|---lib_download.php   文件下载操作类库
|-index.php 入口文件
```

### 2、入口文件 /app/index.php
```
<?php
    define('BASEPATH', dirname(__FILE__));  //定义基本路径常量
    require dirname(__FILE__).'/system/app.php'; //引入系统的驱动类
    require dirname(__FILE__).'/config/config.php';  //引入配置文件
    Application::run($CONFIG);  //运行一个应用实例
```

> 入口文件做了3件事，第一是定义一个全局常量，这个常量非常重要，其他所有文件都要首先判断该常量是否存在，不存在就退出，这就保证了单一入口了。第二是引入系统的驱动类`/app/system/app.php`，第三是引入了应用配置文件。然后运行一个应用实例。


### 3、应用配置文件/app/config/config.php
```
<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    $CONFIG['system']['db'] = array(
        'dsn'           => 'mysql:host=localhost;dbname=kechuang;port=3306',
        'db_user'       => 'root',
        'db_pass'       => '123456',
        'db_conn'       => false,   //是否长连接
        'table_prefix'      => 'app_',
        'db_charset'    => 'utf8',
    );

    $CONFIG['system']['lib'] = array(
        'prefix'            => 'my'  //自定义类库的文件前缀
    );

    $CONFIG['system']['route'] = array(
        'default_controller'    => 'test', //系统默认控制器
        'default_action'        => 'index', //系统默认方法
        'url_type'      => 2,   /* 1为普通模式,index.php?m=module&c=controller&a=action&id=2
                                 * 2为pathinfo模式,index.php/module/controller/action/id/2
                                 */
    );

    $CONFIG['system']['cache'] = array(
        'cache_dir'     => 'cache',  //缓存路径
        'cache_prefix'      => 'cache_', //缓存文件名前缀
        'cache_time'        => 1800,     //缓存时间默认1800s
        'cache_mode'        => 2,        //1为serialize,2为可执行文件
    );
```

> 这里定义了`$CONFIG['system']`数组，表示是系统的配置文件，你可以在如`$CONFIG['myconfig']`数组里定义自定义配置。当然，最好的做法还是把系统配置和用户配置分开，这将在以后有空再改进。

### 4、系统驱动文件/app/system/app.php
```
<?php
    if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    /**
     *应用驱动类
     */
    define('DS', DIRECTORY_SEPARATOR);  //路径分割符
    define('SYSTEM_PATH', dirname(__FILE__));
    $tmp = substr(SYSTEM_PATH, 0, strripos(SYSTEM_PATH, DS));

    define('ROOT_PATH',  $tmp);
    define('SYS_LIB_PATH', SYSTEM_PATH.DS.'lib');
    define('APP_LIB_PATH', ROOT_PATH.DS.'lib');
    define('SYS_CORE_PATH', SYSTEM_PATH.DS.'core');
    define('CONTROLLER_PATH', ROOT_PATH.DS.'controller');
    define('MODEL_PATH', ROOT_PATH.DS.'model');
    define('VIEW_PATH', ROOT_PATH.DS.'view');
    define('LOG_PATH', ROOT_PATH.DS.'error');

    final class Application
    {
        public static $_lib = null;
        public static $_config = null;

        public static function init()
        {
            self::setAutoLibs();
            require SYS_CORE_PATH.DS.'model.php';
            require SYS_CORE_PATH.DS.'controller.php';
        }

        /**
         * 创建应用
         * @access      public
         * @param       array   $config
         */
        public static function run($config)
        {
            self::$_config = $config['system'];
            self::init();
            self::autoload();
            self::$_lib['route']->setUrlType(self::$_config['route']['url_type']);
            $url_array = self::$_lib['route']->getUrlArray();
            self::routeToCm($url_array);
        }

        /**
         * 自动加载类库
         * @access      public
         * @param       array   $_lib
         */
        public static function autoload()
        {
            foreach (self::$_lib as $key => $value)
            {
                require (self::$_lib[$key]);
                $lib = ucfirst($key);
                self::$_lib[$key] = new $lib;
            }
            //初始化cache
            if ( is_object(self::$_lib['cache']) )
            {
                self::$_lib['cache']->init(
                    ROOT_PATH.DS.self::$_config['cache']['cache_dir'],
                    self::$_config['cache']['cache_prefix'],
                    self::$_config['cache']['cache_time'],
                    self::$_config['cache']['cache_mode']
                );
            }
        }

        /**
         * 加载类库
         * @access      public
         * @param       string  $class_name 类库名称
         * @return      object
         */
        public static function newLib($class_name)
        {
            $app_lib = $sys_lib = '';
            $app_lib = APP_LIB_PATH.DS.''.self::$_config['lib']['prefix'].'_'.$class_name.'.php';
            $sys_lib = SYS_LIB_PATH.DS.'lib_'.$class_name.'.php';

            if ( file_exists($app_lib) )
            {
                require ($app_lib);
                $class_name = ucfirst(self::$_config['lib']['prefix']).ucfirst($class_name);
                return new $class_name;
            } else if ( file_exists($sys_lib) ) {
                require ($sys_lib);
                return self::$_lib['$class_name'] = new $class_name;
            } else {
                        trigger_error('加载 '.$class_name.' 类库不存在');
            }
        }

        /**
         * 自动加载的类库
         * @access      public
         */
        public static function setAutoLibs(){
            self::$_lib = array(
                'route'              =>      SYS_LIB_PATH.DS.'lib_route.php',
                'pdomysql'             =>      SYS_LIB_PATH.DS.'lib_pdomysql.php',
                'template'        =>      SYS_LIB_PATH.DS.'lib_template.php',
                'cache'            =>      SYS_LIB_PATH.DS.'lib_cache.php',
                'thumbnail'      =>      SYS_LIB_PATH.DS.'lib_thumbnail.php',
                'crypt'              =>      SYS_LIB_PATH.DS.'lib_crypt.php',
                'download'              =>      SYS_LIB_PATH.DS.'lib_download.php'
            );
        }

        /**
         * 根据URL分发到Controller和Model
         * @access      public
         * @param       array   $url_array
         */
        public static function routeToCm($url_array = array())
        {
            $app = '';
            $controller = '';
            $action = '';
            $model = '';
            $params = '';

            if( isset($url_array['app']) )
            {
                $app = $url_array['app'];
            }

            if( isset($url_array['controller']) && $url_array['controller'] != '' )
            {
                $controller = $model = $url_array['controller'];
                if ( $app )
                {
                    $controller_file = CONTROLLER_PATH.DS.$app.DS.$controller.'Controller.php';
                    $model_file = MODEL_PATH.DS.$app.DS.$model.'Model.php';
                } else {
                    $controller_file = CONTROLLER_PATH.DS.$controller.'Controller.php';
                    $model_file = MODEL_PATH.DS.$model.'Model.php';
                }
            } else {
                $controller = $model = self::$_config['route']['default_controller'];
                if ( $app ) {
                    $controller_file = CONTROLLER_PATH.DS.$app.DS.self::$_config['route']['default_controller'].'Controller.php';
                    $model_file = MODEL_PATH.DS.$app.DS.self::$_config['route']['default_controller'].'Model.php';
                } else {
                    $controller_file = CONTROLLER_PATH.DS.self::$_config['route']['default_controller'].'Controller.php';
                    $model_file = MODEL_PATH.DS.self::$_config['route']['default_controller'].'Model.php';
                }
            }

            if ( isset($url_array['action']) && $url_array['action'] != '' )
            {
                $action = $url_array['action'];
            } else {
                $action = self::$_config['route']['default_action'];
            }

            if( isset($url_array['params']) )
            {
                $params = $url_array['params'];
            }

            if ( file_exists($controller_file) )
            {
                if ( file_exists($model_file) )
                {
                    require $model_file;
                }
                require $controller_file;
                $controller = $controller.'Controller';
                $controller = new $controller;

                if ( $action )
                {
                    if ( method_exists($controller, $action) ) {
                        isset($params) ? $controller ->$action($params) : $controller ->$action();
                    } else {
                        die('控制器方法不存在');
                    }
                } else {
                    die('控制器方法不存在');
                }
            } else {
                die('控制器不存在');
            }
        }

    }
```

> 系统驱动类，用于启动框架，做一些初始化工作。下面将详细分析具体每个方法的作用：

- 1、首先定义一些常量，不解释。
- 2、`setAutoLibs`方法，用于设定那些系统启动时自动加载的类库，类库文件在`SYS_LIB_PATH`目录下，以`lib_`开头。
- 3、`autoload`方法，用于引入你要自动加载的类，然后实例化，用`$_lib`数组来保存类的实例。比如`$_lib['route']`就是`/app/system/lib/lib_route.php`中的`Route`类的实例。
- 4、`newLib`方法，用于加载用户自定义类库，自定义类库保存在`APP_LIB_PATH`目录下。自定义类库的命名按配置文件里的配置加前缀，类的命名则是大驼峰法规则。例如文件名是`my_test.php`，则类名是`MyTest`。
- 5、`init`方法，就是一个初始化方法，里面加载自动加载类，引入核心控制器和模型。
- 6、`run`方法，用于启动框架。里面最后两步很重要，要获取URL然后组装成数组，并交给`routeToCm`方法分发给控制器和模型。
- 7、`routeToCm`方法，根据URL分发到控制器和模型。

### 5、系统核心类库--路由管理类/app/system/lib/lib_route.php
```
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
```

> `setUrlType`方法用于设置URL类型，`makeUrl`方法根据URL类型来调用相应方法获取`$this->route_url`数组。其中`queryToArray`方法将query形式的URL转化为数组，`pathinfoToArray`方法将pathinfo形式的URL转化为数组。

### 6、核心控制器/app/system/core/controller.php
```
<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 核心控制器
 */
class Controller
{

    public function __construct() { }

    /**
     * 实例化模型
     * @access      final   protected
     * @param       string  $model  模型名称
     */
    final protected function model($model)
    {
        if ( empty($model) )
        {
            trigger_error('不能实例化空模型');
        }
        $model_name = $model . 'Model';
        return new $model_name;
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

    /**
     * 加载模板文件
     * @access      final   protect
     * @param       string  $path   模板路径
     * @return      string  模板字符串
     */
    final protected function showTemplate($path, $data = array())
    {
        $template =  $this->load('template');
        $template->init($path, $data);
        $template->outPut();
    }
}
```
> 核心控制器实现了实例化模型，加载类库，模板输出等基础功能。

### 7、核心模型/app/system/core/model.php
```
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
}
```

> 核心模型实现的功能超级简单，就是初始化数据库连接，获得一个数据库连接实例。这里的数据库操作类我采用了单例模式的设计，具体可查看`SYSTEM_LIB_PATH`目录下的`lib_pdomysql.php`。

### 8、系统核心类库--模板操作类/app/system/lib/lib_template.php
```
<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 模板类
 */
final class Template
{
    public $template_name = null;
    public $data = array();
    public $out_put = null;

    public function init($template_name, $data = array())
    {
        $this->template_name = $template_name;
        $this->data = $data;
        $this->fetch();
    }

    /**
     * 加载模板文件
     * @access      public
     * @param       string  $file
     */
    public function fetch()
    {
        $view_file = VIEW_PATH.DS.$this->template_name.'.php';
        if ( file_exists($view_file) )
        {
            extract($this->data);
            ob_start();
            include $view_file;
            $content = ob_get_contents();
            ob_end_clean();
            $this->out_put =  $content;
        } else {
            trigger_error('加载 ' . $view_file . ' 模板不存在');
        }
    }

    /**
     * 输出模板
     * @access      public
     * @return      string
     */
    public function outPut()
    {
        echo $this->out_put;
    }

    /**
     * 写入静态化文件
     * @access      public
     */
    public function toHtml()
    {
        if ( ! is_dir(ROOT_PATH.DS.'cache'.DS.'template') )
        {
            mkdir(ROOT_PATH.DS.'cache'.DS.'template', 0777);
        }
        if ( ! $fp = @fopen(ROOT_PATH.DS.'cache'.DS.'template'.DS.$filename.'.html', 'w') )
        {
            trigger_error('文件 ' .ROOT_PATH.DS.'cache'.DS.'template'.DS.$filename.'.html'. ' 不能打开');
        }
        if ( fwrite($fp, $content) == FALSE )
        {
            trigger_error('文件 ' .ROOT_PATH.DS.'cache'.DS.'template'.DS.$filename.'.html'. ' 写入失败');
        }
        fclose($fp);
    }
}
```
>  `init`方法设置模板文件名，要传递的参数，交给`fetch`方法来渲染。`toHtml`方法用于将模板静态化。其实核心控制器中的`showTemplate`方法就是实现了加载模板类库，传递参数`$data`，然后输出模板。