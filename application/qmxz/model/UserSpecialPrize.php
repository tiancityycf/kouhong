<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场模型类
 */
class UserSpecialPrize extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['user_id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        foreach (['status'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        $query->where('use_type', 1);

        $query->order('id desc');

        return $query;
	}
}