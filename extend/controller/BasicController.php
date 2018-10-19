<?php

namespace controller;

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
		//暂时去掉签名验证
//		if (!preg_match('/\/user\/update$/i',Request::path()) && !$this->validSign(Request::param())) {
//			echo json_encode(['code' => 500,'msg' => '非法请求'], JSON_UNESCAPED_UNICODE);exit();
//		}
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
		//echo $correntSign;die;
		return $sign === $correntSign;
	}
}