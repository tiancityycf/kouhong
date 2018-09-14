<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use api_data_service\v1_0_0\User as UserService;
use model\User as UserModel;
use think\facade\Config;
use think\facade\Cache;
use think\Db;
use controller\BasicController;

/**
 * 用户控制器类
 */
class User extends BasicController
{
	/**
	 * 用户首页
	 * @return json
	 */
	public function index()
	{

		require_params('openid', 'encryptedData', 'iv');
        $data = Request::param();

        $userService = new UserService();
        $result = $userService->index($data['openid']);

        //查询玩家的燃力币信息
        $coin_info = Db::name('step_coin')->field('coins')->where('openid',$data['openid'])->find();
        $result['coin_info'] = $coin_info;

        //查询玩家目前的步数信息 
        //!若用户当天进行了多次兑换 该步数信息会减去之前兑换的步数信息
        $step_info = $this->decryptedData($data['openid'], $data['encryptedData'], $data['iv']);

        if(!$step_info){
        	return result(200, '解密微信步数失败', 0);
        }
        //当前实际的用户步数
        $result['current_steps'] = $step_info['step'];

        $today = date('Y-m-d',time());
		        
		$exchange_history = Db::name('step_coin_log')->where(['openid'=>$data['openid'],'exchange_date'=>$today])->select();

        if(empty($exchange_history)){
        	//如果用户当天没有兑换过，其当前步数为微信返回的步数
        	$result['step_info'] = $step_info['step'];
        }else{
        	//!若用户当天进行了多次兑换 该步数信息会减去之前兑换的步数信息
        	$today_steps = 0;

        	if(count($exchange_history)==count($exchange_history,1)){

        		 $today_steps = $exchange_history['steps'];   //$today_steps为当天历史兑换的总步数

        	}else{
        
        		foreach ($exchange_history as $k => $v) {
        		 $today_steps += $v['steps'];   //$today_steps为当天历史兑换的总步数
        		}
        	}
		    
		    $result['step_info'] = $step_info['step'] - $today_steps;
        }
        

        return result(200, 'ok', $result);
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/user/login.html?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
		require_params('code');
		$code = Request::param('code');
		$from_type = Request::param('from_type') ? Request::param('from_type') : 0;

		$userService = new UserService();
		$result = $userService->login($code, $from_type);

		return result(200, 'ok', $result);
	}

	/**
	 * 更新用户
	 * @return void
	 */
	public function update()
	{
		require_params('openid', 'nickname', 'avatar', 'gender');
		$data = Request::param();
		
		$userService = new UserService();
		$result = $userService->update($data);

		return result(200, 'ok', $result);
	}

	/**
	 * 提现
	 * @return boolean
	 */
	public function withdraw()
	{
		require_params('user_id', 'amount');
		$data = Request::param();

		$userService = new UserService();
		$result = $userService->withdraw($data);

		return result(200, 'ok', $result);
	}

	/**
	 * 提现记录
	 * @return array
	 */
	public function withdrawList()
	{
		require_params('user_id');
		$userId = Request::param('user_id');

		$userService = new UserService();
		$withdrawList = $userService->getWithdrawList($userId);

		return result(200, 'ok', ['withdraw_list' => $withdrawList]);
	}


	/**
	 * 个人用户中心
	 * @return array
	 */

	public function center()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/user/center.html?openid=1&sign=0a53bf188436d7372adfa7e613217f01&timestamp=1
		require_params('openid');
        $openid = Request::param('openid');

        $userInfo = new UserModel();
        $result = $userInfo->field('nickname,avatar')->where('openid',$openid)->find();
        return result(200, 'ok', $result);
	}

	/**
	 * 兑换燃力币
	 * @return json
	 */

	public function exchang_coin()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/user/exchang_coin.html?openid=1&sign=123&&timestamp=1&encryptedData=123&iv=123;
		require_params('openid', 'encryptedData', 'iv');
        $data = Request::param();
        $step_info = $this->decryptedData($data['openid'], $data['encryptedData'], $data['iv']);
        if($step_info){
        		/*
        		微信解密后的当天步数信息数组格式为：
        		array(2) {
				  ["timestamp"] => int(1536854400)
				  ["step"] => int(2135)
				}
				*/
				//获取微信解密的当天的步数信息
        		$data['steps'] = $step_info['step'];
		        //获取配置信息
		        $config = Cache::get(config('config_key'));
		        
		        $today = date('Y-m-d',time());
		        
		        $exchange_history = Db::name('step_coin_log')->where(['openid'=>$data['openid'],'exchange_date'=>$today])->select();
		        if(empty($exchange_history)){
		        		//如果当天没有进行兑换
		        		//获取步数兑换燃力币的比例
				        $exchange_rate = $config['exchange_rate']['value'];
				        //目前兑换比例为6/10000；
				        $coins = number_format($data['steps'] * $exchange_rate,4);

				        if($coins){
				        	 $res1 = Db::name('step_coin')->where('openid',$data['openid'])->setInc('coins',$coins);

				        	 $insert_data = [
				        	 		'openid' => $data['openid'],
				        	 		'steps'  => $data['steps'],
				        	 		'get_coins' => $coins,
				        	 		'exchange_date' => date('Y-m-d',time()),
				        	 		'create_time' => time()
				        	 ];

				        	 $res2 = Db::name('step_coin_log')->insert($insert_data);
				        	 if($res1 !== false && $res2 !== false){
				        	 		return result(200, '0k', ['step'=>0,'get_coins'=>$coins]);
				        	 }
				        }

		        }else{
		        	 //如果当天进行兑换了 1.不许其超过每日兑换步数上限 2.兑换步数需要减去之前兑换的步数
		        	//获取配置设置的每天兑换步数上限值
		        	$exchange_step_limit = $config['exchange_step_limit']['value'];

		        	$today_steps = 0;

		        	//一维数组与多维数组的判断
		        	if(count($exchange_history)==count($exchange_history,1)){

		        		 $today_steps = $exchange_history['steps'];   //$today_steps为当天历史兑换的总步数

		        	}else{
		        		foreach ($exchange_history as $k => $v) {
		        		 $today_steps += $v['steps'];   //$today_steps为当天历史兑换的总步数
		        		}
		        	}
        	
		        	$exchange_steps = $data['steps']- $today_steps;  //$exchange_steps为目前计算后需要兑换的步数

		        	if ($exchange_steps + $today_steps >= $exchange_step_limit) {
		        		//核实用户兑换步数值是否超过每日兑换步数上限
		        		return result(200, '您本次兑换步数已经超过每日兑换步数上限', ['step'=>0,'get_coins'=>$coins]);

		        	}
		        	if($exchange_steps > 0){
	        			$exchange_rate = $config['exchange_rate']['value'];
				        //目前兑换比例为6/10000；
				        $coins = number_format($exchange_steps * $exchange_rate,4);

				        if($coins){
				        	 $res1 = Db::name('step_coin')->where('openid',$data['openid'])->setInc('coins',$coins);

				        	 $insert_data = [
				        	 		'openid' => $data['openid'],
				        	 		'steps'  => $exchange_steps,
				        	 		'get_coins' => $coins,
				        	 		'exchange_date' => date('Y-m-d',time()),
				        	 		'create_time' => time()
				        	 ];

				        	 $res2 = Db::name('step_coin_log')->insert($insert_data);
				        	 if($res1 !== false && $res2 !== false){
				        	 		return result(200, '0k', ['step'=>0,'get_coins'=>$coins]);
				        	 }
				        }

		        	}else{

		        		return result(200, '当前历史兑换步数大于或等于微信返回的步数', ['get_coins'=>0]);
		        	}

		        }  

        }else{

        		return result(200, '解密微信步数失败', 0);
        }

	}



  /**
   * 解密分享数据
   * @param $userId
   * @param $encryptedData
   * @param $iv
   * @see [加密数据解密算法](https://developers.weixin.qq.com/miniprogram/dev/api/signature.html#wxchecksessionobject)
   * @return 
   */
  
     public static function decryptedData($openid, $encryptedData, $iv)
    {

      $UserModel = new UserModel();

      $user = $UserModel->where(['openid'=>$openid])->find();

      $sessionKey = $user['session_key'];

      if (strlen($sessionKey) != 24) {
        //1: session_key格式不正确
        return 1;
      }

      if (strlen($iv) != 24) {
        //2: iv格式不正确
        return 2;
      }

      $aesKey = base64_decode($sessionKey);
      $aesIV = base64_decode($iv);
      $aesCipher = base64_decode($encryptedData);
      $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

      //{"openGId":"tGDKhM5UF0H8J6TGG_Wh2Z4DTSsnA","watermark":{"timestamp":1535624932,"appid":"wx23ec7bcc4f962d4e"}}"
      //dump($result);die;
      $dataObj = json_decode($result,true);

      //成功验证后
      if ($dataObj['watermark']['appid'] == Config::get('wx_appid')) {
      		//返回第三十天的步数信息
			return $dataObj['stepInfoList'][30];
	  }else{

	  	   return false;
	  }

    }



}