<?php

namespace app\api\controller\v1_0_2;

use think\Controller;
use think\facade\Request;
use think\facade\Config;

/**
 * 基础控制器类
 */
class BasicController extends Controller
{
	/**
	 * 初始化
	 * @return void
	 */
	protected function initialize()
	{
		// 校验平台参数
		require_params('sign', 'timestamp');

		if (!$this->validSign(Request::param())) {
			throw new \Exception("非法请求");
		}
	}

	/**
	 * 验证签名
	 * @param  array $params 请求参数
	 * @return bool
	 */
	private function validSign($params)
	{
		$sign = $params['sign'];
		unset($params['sign']);
		ksort($params);

		$primary = '';
		foreach ($params as $key => $value) {
			$primary .= $key . ':' . $value;
		}

		$secret = Config::get('app_secret');
		$correntSign = md5($primary . $secret);

		return $sign === $correntSign;
	}
}