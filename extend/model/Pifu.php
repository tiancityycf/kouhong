<?php

namespace model;

use think\Model;

/**
 * 奖品模型类
 */
class Pifu extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['name'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        return $query;
	}
}