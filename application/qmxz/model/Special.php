<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场模型类
 */
class Special extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        return $query;
	}
}