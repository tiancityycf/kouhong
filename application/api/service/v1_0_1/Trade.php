<?php
namespace app\api\service\v1_0_1;

use app\api\model\WithdrawLog as WithdrawLogModel;
/**
 * 红包服务类
 */
class Trade
{
	/**
	 * 生成16位交易单号
	 * @return string
	 */
	public static function generateTradeNo()
	{
		//return 'Z1'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

		$str = 'H1'.date('ymdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 4);

		$model = WithdrawLogModel::where('trade_no', $str)->find();

		if ($model) {
			return self::generateTradeNo();
		}

		return $str;
	}
}