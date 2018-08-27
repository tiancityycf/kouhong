<?php

namespace app\admin\model;

use think\Model;

/**
 * 交易日志模型类
 */
class WithdrawLog extends Model
{
	protected $connection = [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '172.16.1.66',
        // 数据库名
        'database'        => 'dbssszw',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => 'mdWeb^Serv$MYSQL',
    ];
}