<?php

namespace app\api\model;

use think\Model;

/**
 * 交易日志模型类
 */
class WithdrawLog extends Model
{
	//public $table = 't_withdraw_log';
	/**
	 * 获取提现记录
	 * @param  integer $userId 用户id
	 * @return array
	 */
	public function getWithdrawList($userId)
	{
		$withdrawList = self::where('user_id', $userId)->select();
		$result = [];
        foreach ($withdrawList as $key => $withdraw) {
            $result[$key] = [
            	'trade_no' => $withdraw['trade_no'],
                'amount' => $withdraw['amount'],
                'create_time' => $withdraw['create_time'],
            ];
        }

        return $result;
	}
}