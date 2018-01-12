<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 测试模型
 */
class testModel extends Model
{

    function index()
    {
        echo 'hello world';
    }

    function get_all_db()
    {
        //return $this->db->dbprefix('admins');
        return $this->db->query('show tables');
    }
}