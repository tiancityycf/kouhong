<?php
namespace app\api\service\v1_0_1;

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
		return 'X1'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	}
}