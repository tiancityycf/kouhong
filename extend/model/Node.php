<?php

namespace model;

use think\Model;

/**
 * 挑战日志模型类
 */
class Node extends Model
{
	// 设置当前模型对应的完整数据表名称
    protected $table = 't_system_node';
    protected $connection = 'db_base';

//    protected $connection = [
//		// 数据库类型
//	    'type'            => 'mysql',
//	    // 服务器地址
//	    'hostname'        => 'localhost',
//	    // 数据库名
//	    'database'        => 'ceshi_jichudatabase',
//	    // 用户名
//	    'username'        => 'root',
//	    // 密码
//	    'password'        => 'root',
//	];
}