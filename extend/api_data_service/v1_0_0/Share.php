<?php

namespace api_data_service\v1_0_0;

use think\Db;
use think\facade\Config;
use api_data_service\Log as LogService;
use api_data_service\Config as ConfigService;
use model\User as UserModel;
use model\UserRecord as UserRecordModel;
use model\ShareLog as ShareLogModel;
use model\ShareCount as ShareCountModel;

/**
 * 用户分享服务类
 */
class Share
{
	public static $OK = 0;
	public static $IllegalAesKey = -41001;
	public static $IllegalIv = -41002;
	public static $IllegalBuffer = -41003;
	public static $DecodeBase64Error = -41004;

	/**
	 * 分享
	 * @param  $data 请求数据
	 * @return boolean
	 */
	public function share($data)
	{
		switch ($data['share_type']) {
			case '1':
				return $this->shareUser($data);
				break;
			case '2':
				return $this->shareGroup($data);
				break;
			default:
				throw new \Exception();
				break;
		}
	}

	/**
	 * 分享给用户
	 * @param  $data 请求数据
	 * @return void
	 */
	public function shareUser($openid)
	{
		$getSteps = 0;

		//获取配置信息
        $config = Cache::get(config('config_key'));

		// 判断是否开启分享到个人用户分享次数与奖励步数配置
        if(!isset($config['share_person_limit']) || !isset($config['share_person_getStep'])){
        	return ['code' => 3000, 'message' => '未开启个人分享的配置'];
        }

        //判断个人用户分享次数是否达到每天限制
        $day = date('Y-m-d');
        $is_shared = Db::name('share_count')->where(['openid'=>$openid,'share_day'=>$day])->select();
        if(empty($is_shared)){
        	//当日没有分享过
        	 $insert_data = [
        	 	'openid'=> $openid,
        	 	'create_time' => time(),
        	 	'openGid' => 0,
        	 	'share_day' => $day
        	 ];
        	 //记录分享次数表
        	 Db::name('share_count')->insert($insert_data);
        	 
        	 //插入分享或签到后的步数日志信息
        	 $steps_data = [
        	 	   'openid' => $openid,
        	 	   'steps'  => $config['share_person_getStep'],
        	 	   'type'  => 2
         	 ];
        	 $this->add_steps($steps_data);

        	 return ['code' => 3010, 'message' => '分享个人成功','data'=>$config['share_person_getStep']];
        }else{
        	//当日有分享过
        	if(count($is_shared)>=$config['share_person_limit']){

        		return ['code' => 3020, 'message' => '分享个人次数以达上限'];
        	};

        	 $insert_data = [
        	 	'openid'=> $openid,
        	 	'create_time' => time(),
        	 	'openGid' => 0,
        	 	'share_day' => $day
        	 ];
        	 //记录分享次数表
        	 Db::name('share_count')->insert($insert_data);

        	  //插入分享或签到后的步数日志信息
        	 $steps_data = [
        	 	   'openid' => $openid,
        	 	   'steps'  => $config['share_person_getStep'],
        	 	   'type'  => 2
         	 ];
        	 $this->add_steps($steps_data);

        	 return ['code' => 3010, 'message' => '分享个人成功','data'=>$config['share_person_getStep']];
        }

	}

	/**
	 * 插入分享或签到后的步数日志信息
	 * @return null
	 */
	public function add_steps($data)
	{
		if($data['type'] == 1){

			$comment = '签到奖励';

		}else if($data['type'] == 2){

			$comment = '邀请好友';
		}else{

			$comment = '分享到群';
		}
		$insert_data = [
				'openid'=>$data['openid'],
				'steps'=>$data['steps'],
				'create_time' => time(),
				'comment' => $comment,
		];
		Db::name('step')->insert($insert_data);
	}




	/**
	 * 分享到群
	 * @param  $data 请求数据
	 * @return void
	 */
	private function shareGroup($data)
	{
		//获取配置信息
        $config = Cache::get(config('config_key'));

		// 判断是否开启分享到个人用户分享次数与奖励步数配置
        if(!isset($config['share_group_limit']) || !isset($config['share_group_getStep'])){
        	return ['code' => 2000, 'message' => '未开启群分享的配置'];
        }

        //解密群数据
        $share_gruop_info = $this->decryptedData($data['openid'],$data['encryptedData'],$data['iv']);
       	
       	if(!$share_gruop_info) return ['code' => 0, 'message' => '解密群数据失败'];
        //判断用户分享到群次数是否达到每天限制且判断是否分享到了重复的群
        $day = date('Y-m-d');
        $is_shared = Db::name('share_count')->where(['openid'=>$data['openid'],'share_day'=>$day])->select();

        if(empty($is_shared)){
        	//当日没有分享过
        	 $insert_data = [
        	 	'openid'=> $data['openid'],
        	 	'create_time' => time(),
        	 	'openGid' => $share_gruop_info['openGId'],
        	 	'share_day' => $day
        	 ];
        	 //记录分享次数表
        	 Db::name('share_count')->insert($insert_data);
        	 
        	 //插入分享或签到后的步数日志信息
        	 $steps_data = [
        	 	   'openid' => $data['openid'],
        	 	   'steps'  => $config['share_group_getStep'],
        	 	   'type'  => 3
         	 ];
        	 $this->add_steps($steps_data);

        	 return ['code' => 2010, 'message' => '分享到群成功','data'=>$config['share_group_getStep']];
        }else{
        	   //当日有分享过
        	   //判断是否分享到了重复的群
	        foreach ($is_shared as $k => $v) {

	        	if($share_gruop_info['openGId'] == $v['openGId']){

	        		return ['code' => 2020, 'message' => '分享到了重复的群'];
	        	}
	        }

        	if(count($is_shared)>=$config['share_group_limit']){

        		return ['code' => 3020, 'message' => '分享群次数以达上限'];
        	}

        	 $insert_data = [
        	 	'openid'=> $data['openid'],
        	 	'create_time' => time(),
        	 	'openGid' => $share_gruop_info['openGId'],
        	 	'share_day' => $day
        	 ];
        	 //记录分享次数表
        	 Db::name('share_count')->insert($insert_data);

        	  //插入分享或签到后的步数日志信息
        	 $steps_data = [
        	 	   'openid' => $data['openid'],
        	 	   'steps'  => $config['share_group_getStep'],
        	 	   'type'  => 3
         	 ];
        	 $this->add_steps($steps_data);

        	 return ['code' => 2010, 'message' => '分享到群成功','data'=>$config['share_group_getStep']];
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
        return false;
      }

      if (strlen($iv) != 24) {
        //2: iv格式不正确
        return false;
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
      		//返回群解密数据
			return $dataObj;
	  }else{

	  	   return false;
	  }

      
    }

	
}