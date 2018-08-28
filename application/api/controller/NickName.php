<?php

namespace app\api\controller;

use think\facade\Request;
use think\Controller;
use model\Heimingdan as HeimingdanModel;
use model\Whitelist as WhitelistModel;

class NickName extends Controller
{
	/**
	 * 验证签名
	 * @param  array $params 请求参数
	 * @return bool
	 */
	private function validSign($params)
	{
		require_params('sign', 'timestamp');
		
		$sign = $params['sign'];
		unset($params['sign']);
		ksort($params);

		$model = WhitelistModel::where('appid', $params['appid'])->where('status',1)->find();

		if (!$model) {
			echo json_encode(['code' => 500,'msg' => '非法请求'], JSON_UNESCAPED_UNICODE);exit();
		}

		$ip = Request::ip();
		if (!in_array($ip, explode('; ', $model->ips))) {
			echo json_encode(['code' => 500,'msg' => '非法请求'], JSON_UNESCAPED_UNICODE);exit();
		}

		$primary = '';
		foreach ($params as $key => $value) {
			$primary .= $key . ':' . $value;
		}

		$secret = $model->app_secret;

		$correntSign = md5($primary . $secret);

		if ($sign !== $correntSign) {
			echo json_encode(['code' => 500,'msg' => '非法请求'], JSON_UNESCAPED_UNICODE);exit();
		}

		/*$primary = '';
		foreach ($params as $key => $value) {
			$primary .= $key . ':' . $value;
		}

		$secret = config('app_secret');

		$correntSign = md5($primary . $secret);

		if ($sign !== $correntSign) {
			echo json_encode(['code' => 500,'msg' => '非法请求'], JSON_UNESCAPED_UNICODE);exit();
		}*/
	}

	private function check($nickname)
	{
	    //source  小程序跳转来源 1-搜索跳转 2-从搜索分享点击跳转 3-直接跳转过来
        //1-搜索跳转 2-从搜索分享点击跳转  会直接显示 属于黑名单  3-直接跳转过来 需要判断是否在系统黑名单表中
        $source = Request::param('source');
	    if($source==1 || $source==2 ){
            $user_status = 0;
        }else{
            $model = HeimingdanModel::where('nickname', $nickname)->where('status', 0)->find();
            if ($model) {
                $user_status = 0;
            } else {
                $user_status = 1;
            }
        }
		return ['user_status' => $user_status];
	}

	public function index()
	{
		require_params('nickname', 'appid');
        $this->validSign(Request::param());

        $nickname = Request::param('nickname');

        $result = $this->check($nickname);

        return result(200, 'ok',$result);
	}
}