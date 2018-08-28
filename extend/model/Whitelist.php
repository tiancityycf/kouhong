<?php

namespace model;

use think\Model;

/**
 * 挑战日志模型类
 */
class Whitelist extends Model
{
	public function search($params, $admin_user_id)
	{
		$query = self::buildQuery();

        foreach (['appid', 'ips', 'title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        if ($admin_user_id != 10000) {
        	$query->where('admin_user_id', $admin_user_id);
        }
        

        return $query;
	}
}