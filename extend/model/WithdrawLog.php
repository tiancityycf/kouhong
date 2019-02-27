<?php

namespace model;

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

        foreach (['user_id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        if (isset($params['status']) && $params['status'] !== '') {
            if ($params['status'] == 0) {
                $query->where('status', '<>', 1);
            } else if ($params['status'] == 1) {
                $query->where('status', '=', 1);
            }
        }

        if (isset($params['create_time']) && $params['create_time'] !== '') {
            list($start_create_time, $end_create_time) = explode(' - ', $params['create_time']);
            $query->whereBetweenTime('create_time', "{$start_create_time}", "{$end_create_time}");
        }

        // if (!isset($params['status']) && empty($params['user_id']) && empty($params['trade_no']) && empty($params['create_time'])) {
        //     $query->where('status', '<>', 1);
        // }

        $query->order('id', 'desc');

        return $query;
	}


    /**
     * 获取提现记录
     * @param  integer $userId 用户id
     * @return array
     */
    public function getWithdrawList($userId)
    {
        $withdrawList = self::where('user_id', $userId)->order('id desc')->select();
        $result = [];
        foreach ($withdrawList as $key => $withdraw) {
            $result[$key] = [
                'trade_no' => $withdraw['trade_no'],
                'amount' => $withdraw['amount'],
                'create_time' => $withdraw['create_time'],
                'status' => $withdraw['status'],
            ];
        }

        return $result;
    }
}