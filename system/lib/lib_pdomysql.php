<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Pdo 操作类
 */
final class Pdomysql
{
    private $dsn;  //数据库dsn
    private $db_user; //数据库用户名
    private $db_pwd; //数据库用户名密码
    private $connection; //数据库连接标识;
    private $db_charset; //数据库编码，GBK,UTF8,gb2312
    private $pdn = null;
    private $stmt = null;

    static private $_instance;

    private $result; //执行query命令的结果资源标识
    private $sql; //sql执行语句
    private $row; //返回的条目数



    /**
     * 构造函数
     */
    public function __construct()
    {
        $config_db = $this->config('db');
        $this->dsn = $config_db['dsn'];
        $this->db_user = $config_db['db_user'];
        $this->db_pwd = $config_db['db_pass'];
        $this->connection = $config_db['db_conn'];
        $this->db_charset = $config_db['db_charset'];
        $this->connect();
        $this->pdn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->pdn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->pdn->exec('SET NAMES ' . $this->db_charset);
        return $this->pdn;
    }

    /**
     * 防止被克隆
     */
    private function __clone() {}

    /**
     * 获取类的实例，单实例
     */
    public static function getInstance()
    {
        if ( FALSE === (self::$_instance instanceof self) )
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 数据库连接
     */
    private function connect()
    {
        try {
            $this->pdn = new PDO($this->dsn, $this->db_user, $this->db_pwd, array(PDO::ATTR_PERSISTENT => $this->connection));
        } catch ( PDOException $e ) {
            die( 'Connect Error Infomation:' . $e->getMessage () );
        }
    }
    /**
     * 关闭数据库连接
     */
    private function close()
    {
        $this->pdn = null;
    }

    /**
     * 加载系统配置
     */
    private function config($config = ''){
        return Application::$_config[$config];
    }

    /**
     *数据库执行语句，可执行查询相关sql语句
     */
    public function query($sql, $type = '1', $mode = PDO::FETCH_OBJ)
    {
        $this->stmt = $this->pdn->query($sql);
        $this->stmt->setFetchMode($mode);
        switch ($type)
        {
            case '0' :
                $this->result = $this->stmt->fetch();
                break;
            case '1' :
                $this->result = $this->stmt->fetchAll();
                break;
            case '2' :
                $this->result = $this->stmt->rowCount();
                break;
        }
        $this->stmt = null;
        return $this->result;
    }

    /**
     * 获取数据表全名
     */
    public function dbprefix($table_name)
    {
        $config_db = $this->config('db');
        return $config_db['table_prefix'].$table_name;
    }

    public function getLastId()
    {
        return $this->pdn->lastInsertId();
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}