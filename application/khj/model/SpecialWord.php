<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场模型类
 */
class SpecialWord extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        foreach (['special_id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        $query->order('id desc');

        return $query;
	}

	public function special()
    {
        return $this->hasOne('Special', 'id', 'special_id');
    }
}