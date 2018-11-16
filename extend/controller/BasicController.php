<?php

namespace controller;

use think\Controller;
use think\facade\Config;
use app\khj\service\Config as ConfigService;

/**
 * 基础控制器类
 */
class BasicController extends Controller
{
	protected $configData = [];
	/**
	 * 初始化
	 * @return void
	 */
	protected function initialize()
	{
		$configService = new ConfigService();
        $config_data   = $configService->getAll();
		$this->configData = $config_data;
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