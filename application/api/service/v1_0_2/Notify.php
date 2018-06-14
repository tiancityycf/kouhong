<?php
namespace app\api\service\v1_0_2;

use think\facade\Config;
use app\api\model\WithdrawLog as WithdrawLogModel;

/**
 * 提现服务类
 */
class Notify
{
	/**
	 * 提现通知
	 * @return string
	 */
	public function withdraw($data)
	{
		if (self::validSign($data)) {
			$withdrawLogModel = WithdrawLogModel::where('trade_no', $data['trade_no'])->find();
			if ($withdrawLogModel) {
				if ($data['code'] == 200) {
					$withdrawLogModel->status = 1; // 成功
					$withdrawLogModel->pay_time = time();
				} else {
					$withdrawLogModel->status = 2; // 失败
				}

				$withdrawLogModel->save();
				return 'SUCCESS';
			}
			return 'FAILUE';
		}
		return 'FAILUE';
	}

	/**
	 * 校验签名
	 * @param  array $data 请求参数
	 * @return boolean
	 */
	public static function validSign($data)
	{
		if (!isset($data['sign'])) {
			return false;
		}

		return $data['sign'] === self::generateSign($data);
	}

	/**
	 * 生成签名
	 * @param  array $data 请求数据
	 * @return string
	 */
	public static function generateSign($data)
	{
		unset($data['sign']);
		ksort($data);

		$primary = '';
		foreach ($data as $key => $value) {
			$primary .= $key . '=' . $value . '&';
		}

		return strtoupper(md5($primary . 'key=' . Config::get('withdraw_secret')));
	}
}