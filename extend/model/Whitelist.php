<?php

namespace model;

use think\Model;

/**
 * 挑战日志模型类
 */
class Whitelist extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

        foreach (['appid', 'ips', 'title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        return $query;
	}
}