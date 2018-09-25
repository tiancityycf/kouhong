<?php

namespace app\api\controller;

use think\facade\Request;
use think\Controller;
use model\Heimingdan as HeimingdanModel;
use model\Whitelist as WhitelistModel;
use service\HttpService;

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
			echo json_encode(['code' => 500,'msg' => '缺少接口秘钥'], JSON_UNESCAPED_UNICODE);exit();
		}
        //因小程序支持云开发 暂时去掉IP限制
        $cloud = ['wx19011e5a73d44e6c'];
        if(!in_array($params['appid'],$cloud)){
            $ip = Request::ip();
            if (!in_array($ip, explode(';', $model->ips))) {
                echo json_encode(['code' => 500,'msg' => '请求IP不合法'], JSON_UNESCAPED_UNICODE);exit();
            }
        }

		$primary = '';
		foreach ($params as $key => $value) {
			$primary .= $key . ':' . $value;
		}

		$secret = $model->app_secret;

		$correntSign = md5($primary . $secret);

		if ($sign !== $correntSign) {
            trace('传的sign='.$sign." 计算sign=".$correntSign.' 加密前='.$primary.$secret,'error');
			echo json_encode(['code' => 500,'msg' => '签名错误'], JSON_UNESCAPED_UNICODE);exit();
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

    /**
     * 验证签名 支持中文和特殊字符
     * @param  array $params 请求参数
     * @return bool
     */
    private function validSignEncode($params)
    {
        require_params('sign', 'timestamp');

        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);

        $model = WhitelistModel::where('appid', $params['appid'])->where('status',1)->find();

        if (!$model) {
            echo json_encode(['code' => 500,'msg' => '缺少接口秘钥'], JSON_UNESCAPED_UNICODE);exit();
        }
        //因小程序支持云开发 暂时去掉IP限制
        $cloud = ['wx19011e5a73d44e6c'];
        if(!in_array($params['appid'],$cloud)){
            $ip = Request::ip();
            if (!in_array($ip, explode(';', $model->ips))) {
                echo json_encode(['code' => 500,'msg' => '请求IP不合法'], JSON_UNESCAPED_UNICODE);exit();
            }
        }

        $primary = '';
        foreach ($params as $key => $value) {
            if($key=='nickname'){
                $primary .= $key . ':' . urlencode($value);
            }else{
                $primary .= $key . ':' . $value;
            }
        }

        $secret = $model->app_secret;

        $correntSign = md5($primary . $secret);

        if ($sign !== $correntSign) {
            trace('传的sign='.$sign." 计算sign=".$correntSign.' 加密前='.$primary.$secret,'error');
            echo json_encode(['code' => 500,'msg' => '签名错误'], JSON_UNESCAPED_UNICODE);exit();
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

	private function check($nickname, $source)
	{
	    //source  小程序跳转来源 1-搜索跳转 2-从搜索分享点击跳转 3-直接跳转过来
        //1-搜索跳转 2-从搜索分享点击跳转  会直接显示 属于黑名单  3-直接跳转过来 需要判断是否在系统黑名单表中
        //$source = Request::param('source');
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
    /**
     * 目的：防止竞争对手举报或微信审核拒绝
     * 需求说明：超级黑名单，开放一个接口，可以判断该昵称是否在黑名单里，如果在黑名单则不显示红包或其他处理，不在黑名单则显示红包或其他处理
     * @param $source  小程序跳转来源 1-搜索跳转 2-从搜索分享点击跳转 3-直接跳转过来
     * 1-搜索跳转 2-从搜索分享点击跳转  会直接显示 属于黑名单  3-直接跳转过来 需要判断是否在系统黑名单表中
     * 接口安全设置：
     * 1.后台可以设置合作伙伴的请求服务器IP白名单，只有在白名单的IP请求才会处理，否则显示非法请求
     * 2.接口请求加数据签名
     */
	public function index()
	{
		require_params('nickname', 'appid');
        trace("app_blacklist params=".json_encode(Request::param(),JSON_UNESCAPED_UNICODE),'info');
        $this->validSign(Request::param());
        $appid = Request::param('appid');
        $nickname = Request::param('nickname');
        $source = Request::param('source');
        //判断黑名单开关是否打开
        $ad_url = config("ad_url").'/api/app_card/status?app_id='.$appid;
        $json = HttpService::get($ad_url,[],['timeout'=>5]);
        $json = json_decode($json,true);
        if($json['data']==1){
            $result = ['user_status' => 1];
        }else{
            $result = $this->check($nickname, $source);
        }
        return result(200, 'ok',$result);
	}

    /**
     * 目的：防止竞争对手举报或微信审核拒绝 新接口 修正了 中文或特殊字符签名不过得问题
     * 需求说明：超级黑名单，开放一个接口，可以判断该昵称是否在黑名单里，如果在黑名单则不显示红包或其他处理，不在黑名单则显示红包或其他处理
     * @param $source  小程序跳转来源 1-搜索跳转 2-从搜索分享点击跳转 3-直接跳转过来
     * 1-搜索跳转 2-从搜索分享点击跳转  会直接显示 属于黑名单  3-直接跳转过来 需要判断是否在系统黑名单表中
     * 接口安全设置：
     * 1.后台可以设置合作伙伴的请求服务器IP白名单，只有在白名单的IP请求才会处理，否则显示非法请求
     * 2.接口请求加数据签名
     */
    public function ck()
    {
        require_params('nickname', 'appid');
        trace("app_blacklist params=".json_encode(Request::param(),JSON_UNESCAPED_UNICODE),'info');
        $this->validSignEncode(Request::param());
        $appid = Request::param('appid');
        $nickname = Request::param('nickname');
        $source = Request::param('source');
        //判断黑名单开关是否打开
        $ad_url = config("ad_url").'/api/app_card/status?app_id='.$appid;
        $json = HttpService::get($ad_url,[],['timeout'=>5]);
        $json = json_decode($json,true);
        if($json['data']==1){
            $result = ['user_status' => 1];
        }else{
            $result = $this->check($nickname, $source);
        }
        return result(200, 'ok',$result);
    }

    /**
     * 验证签名 nickname 不再放到签名中
     * @param  array $params 请求参数
     * @return bool
     */
    private function validSignNew($params)
    {
        require_params('sign', 'timestamp');

        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);

        $model = WhitelistModel::where('appid', $params['appid'])->where('status',1)->find();

        if (!$model) {
            echo json_encode(['code' => 500,'msg' => '缺少接口秘钥'], JSON_UNESCAPED_UNICODE);exit();
        }
        //因小程序支持云开发 暂时去掉IP限制
        $cloud = ['wx19011e5a73d44e6c'];
        if(!in_array($params['appid'],$cloud)){
            $ip = Request::ip();
            if (!in_array($ip, explode(';', $model->ips))) {
                echo json_encode(['code' => 500,'msg' => '请求IP不合法'], JSON_UNESCAPED_UNICODE);exit();
            }
        }

        $primary = '';
        foreach ($params as $key => $value) {
            if($key=='nickname'){
                continue;
//                $primary .= $key . ':' . urlencode($value);
            }else{
                $primary .= $key . ':' . $value;
            }
        }

        $secret = $model->app_secret;

        $correntSign = md5($primary . $secret);

        if ($sign !== $correntSign) {
            trace('传的sign='.$sign." 计算sign=".$correntSign.' 加密前='.$primary.$secret,'error');
            echo json_encode(['code' => 500,'msg' => '签名错误'], JSON_UNESCAPED_UNICODE);exit();
        }
    }
    /**
     * 目的：防止竞争对手举报或微信审核拒绝 新接口 nickname不再放在签名中
     * 需求说明：超级黑名单，开放一个接口，可以判断该昵称是否在黑名单里，如果在黑名单则不显示红包或其他处理，不在黑名单则显示红包或其他处理
     * @param $source  小程序跳转来源 1-搜索跳转 2-从搜索分享点击跳转 3-直接跳转过来
     * 1-搜索跳转 2-从搜索分享点击跳转  会直接显示 属于黑名单  3-直接跳转过来 需要判断是否在系统黑名单表中
     * 接口安全设置：
     * 1.后台可以设置合作伙伴的请求服务器IP白名单，只有在白名单的IP请求才会处理，否则显示非法请求
     * 2.接口请求加数据签名
     */
    public function valid()
    {
        require_params('nickname', 'appid');
        trace("app_blacklist params=".json_encode(Request::param(),JSON_UNESCAPED_UNICODE),'info');
        $this->validSignNew(Request::param());
        $appid = Request::param('appid');
        $nickname = Request::param('nickname');
        $source = Request::param('source');
        //判断黑名单开关是否打开
        $ad_url = config("ad_url").'/api/app_card/status?app_id='.$appid;
        $json = HttpService::get($ad_url,[],['timeout'=>5]);
        $json = json_decode($json,true);
        if($json['data']==1){
            $result = ['user_status' => 1];
        }else{
            $result = $this->check($nickname, $source);
        }
        return result(200, 'ok',$result);
    }
	public function demo(){
	    $params = [];
	    $params['appid'] = 'wx123';
        $params['nickname'] = 'zhangsan';
        $params['source'] = 3;
        $params['timestamp'] = 1;
        $app_secret = 'wx123'; //登录后台管理系统，ip配置菜单，添加自己小程序的信息，获取app_secret
        $url = 'https://hz.zxmn2018.com/api/nick_name/index';
        $params['sign'] = $this->sign($params,$app_secret);
        $result = $this->doRequest($url,$params,$timeout = 5);
        echo $result;
    }

    public function doRequest($url,$params,$timeout = 5){

        if(empty($params) || $timeout <=0){
            return false;
        }
        $con = curl_init();
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POST,true);
        curl_setopt($con, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($con, CURLOPT_TIMEOUT,(int)$timeout);
        $result = curl_exec($con);
        curl_close($con);
        return $result;
    }

    public function sign($params,$app_secret){
        ksort($params);
        $primary = '';
        foreach ($params as $key => $value) {
            $primary .= $key . ':' . $value;
        }
        return md5($primary . $app_secret);
    }
}