<?php

namespace app\admin\model;

use think\Model;

/**
 * 词语模型类
 */
class Word extends Model
{

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
	/**
	 * 获取指定难度下的所有词语id
	 * @param  integer $level 难度等级
	 * @return array
	 */
	public static function getAllIdsByLevel($level)
	{
        return self::where('status', 1)->where('level', $level)->column('id');
	}


	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['word'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        if (isset($params['level']) && $params['level'] !== '') {
            $query->where('level', '=', $params['level']);
        }

        return $query;
	}
}