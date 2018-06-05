<?php
// 定义缓存名称
define('CACHE_APP_NAME', Config::get('cache_app_name'));
define('CACHE_APP_UNIQ', Config::get('cache_app_uniq'));

/**
 * 统一校验参数
 * @param  string ...$params
 * @return string
 */
function require_params(...$params)
{
	foreach ($params as $param) {
		if (!Request::has($param)) {
			throw new \think\exception\ValidateException('缺少必要的参数: ' . $param);
		}
	}
}

/**
 * 统一返回
 * @param  integer $code 状态值
 * @param  string $msg 信息
 * @param  array $data 数据
 * @return json
 */
function result($code = 200, $msg = 'ok', $data = [])
{
	return json([
		'code' => $code,
		'msg' => $msg,
		'data' => $data,
	]);
}

/**
 * 汉字加密
 * @return string
 */
function hanzi_encode($str)
{
	return substr(strtoupper(md5($str)), 10, 10);
}

/**
 * 统一加密函数
 * @param  $str 待加密字符串
 * @return string
 */
function str_encode($str)
{
    return base64_encode($str);
}

/**
 * 统一解密函数
 * @param  $str 加密字符串
 * @return string
 */
function str_decode($str)
{
	return base64_decode($str);
}