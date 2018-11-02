<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场题库模型类
 */
class SpecialWordWarehouse extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
	}
}