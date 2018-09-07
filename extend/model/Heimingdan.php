<?php

namespace model;

use think\Model;

/**
 * 小程序后台黑名单模型类
 */
class Heimingdan extends Model
{
	protected $connection = [
		// 数据库类型
	    'type'            => 'mysql',
	    // 服务器地址
	    'hostname'        => 'localhost',
	    // 数据库名
	    'database'        => 'ceshi_jichu_1002',
	    // 用户名
	    'username'        => 'root',
	    // 密码
	    'password'        => '',
	];

	public function search($params)
	{
		$query = self::buildQuery();
		foreach (['nickname'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
	}


}