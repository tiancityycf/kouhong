<?php

namespace app\admin\model;

use think\Model;

/**
 * 交易日志模型类
 */
class WithdrawLog extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		foreach (['trade_no'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        foreach (['user_id', 'status'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        if (isset($params['create_time']) && $params['create_time'] !== '') {
            list($start_create_time, $end_create_time) = explode(' - ', $params['create_time']);
            $query->whereBetweenTime('create_time', "{$start_create_time}", "{$end_create_time}");
        }

        if (!isset($params['status']) && empty($params['user_id']) && empty($params['trade_no']) && empty($params['create_time'])) {
            $query->where('status', '=', 0);
        }

        $query->order('id', 'desc');

        return $query;
	}
}