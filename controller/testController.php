<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 测试控制器
 */
class testController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['test'] = '什么';
        $this->showTemplate('test', $data);
    }


    public function demo()
    {
        $test = $this->model('test');        //实例化test模型
        $tbs = $test->get_all_db();
        var_dump($tbs);
    }

    public function demo2()
    {
        $crypt = $this->load('crypt');
        echo $crypt->encrypt('tonyxu', '2e4df9');
    }

}