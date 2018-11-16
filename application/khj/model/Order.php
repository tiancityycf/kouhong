<?php

namespace app\khj\model;

use think\Model;

/**
 * 订单模型类
 */
class Order extends Model
{
	public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id', 'user_id', 'status'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}