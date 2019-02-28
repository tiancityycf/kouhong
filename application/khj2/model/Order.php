<?php

namespace app\khj2\model;

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

        foreach (['trade_no'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, "{$params[$key]}");
        }

        if (isset($params['addtime']) && $params['addtime'] !== '') {
            $times_arr = explode(' - ', $params['addtime']);
            $addstart = $times_arr[0];
            $addend = $times_arr[1];
            $query->where('addtime', 'between', [$addstart, $addend]);
        }

        if (isset($params['pay_time']) && $params['pay_time'] !== '') {
            $times_arr = explode(' - ', $params['pay_time']);
            $pay_start = $times_arr[0];
            $pay_end = $times_arr[1];
            $query->where('pay_time', 'between', [$pay_start, $pay_end]);
        }

        $query->order('id desc');

        return $query;
    }
}
