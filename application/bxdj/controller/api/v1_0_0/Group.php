<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use app\bxdj\model\Group as GroupModel;
use app\bxdj\model\Activity as ActivityModel;
use app\bxdj\model\GroupPersons as GroupPersonsModel;
use model\User as UserModel;

use think\Db;
use think\facade\Config;
use think\facade\Cache;
/**
 * 活动——团队控制器类
 */
class Group
{

	/**
	 * 团队首页 1. 展示我的团队 我的贡献步数信息 团队排名等信息 2. 团队赛结束时展示内容
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Group/index.html?openid=oFGa94hlqh7ZTPk90Z0uihyEpGPc&group_id=12;	
		require_params('openid','group_id');  //group_id为参与的团队的id
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//活动参与计算步数的时间
		$today = date("Y-m-d",time());

		//查询申请查看自己团队的人是否进入了该团队
		if(!$is_grouper = $groupPersonsModel->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->find()){

				return ['message'=>'您无权查看非您团队的信息','code'=>2000];

		}else{
			//若通过上述判断，1.展示目前团队的排行榜 2.展示我的团队 我的贡献步数信息逻辑
			//1.展示目前团队的排行榜
			$groups_ranks = $groupModel->where(['status'=>1,'activity_id'=>$is_grouper['activity_id']])->field('id,group_name,group_steps')->order('group_steps desc')->select()->toarray();

			//更新所有团队的即时排名
			foreach ($groups_ranks as $k => $v) {
                $groupModel->where(['id' => $v['id']])->update(['rank'=>$k+1]);
            }
			//返回当前前三的组排名
			$groups_six = array_slice($groups_ranks,0,3);
			
			//查看您自己的团队的具体信息
			$group_info = $groupModel->alias('g')->join('t_group_persons a','g.id = a.group_id')->field('g.id,g.group_steps,g.rank,g.nums,g.status,a.contribute_steps,a.get_reward')->where(['g.id'=>$data['group_id']])->find();

			//2.展示我的团队 我的贡献步数信息逻辑
			$group_persons = $groupPersonsModel->field('avatar')->where(['group_id'=>$data['group_id']])->select();

			$return_data = [
					'rank' => $groups_six,
					'group_info' => $group_info,
					'avatars' => $group_persons
			];

			return ['message'=>'ok','code'=>2002,'data'=>$return_data];
		}

	}	

	/**
	 * 创建团队
	 * @return json
	 */
	public function create_group()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Group/create_group.html?openid=oFGa94hlqh7ZTPk90Z0uihyEpGPc&activity_id=5&group_name='巅峰对决';
		require_params('openid','activity_id','group_name');  //activity_id为参与的活动id, group_name为团队名称
		$data = Request::param();

		//获取微信小程序敏感内容检测的api的接入access_token
		if(Cache::has('access_token')){
				$access_token = Cache::get('access_token');
		}else{
			$appid = Config::get('wx_appid');
    		$secret = Config::get('wx_secret');
			$get_access_url = Config::get('get_access_url');
			$access_token_data = json_decode(file_get_contents(sprintf($get_access_url, $appid, $secret)), true);
			$access_token = $access_token_data['access_token'];
			$access_expires_in = $access_token_data['expires_in'] - 10;
			Cache::set('access_token', $access_token, $access_expires_in);
		}

		//敏感内容检测的api接口url
		$garbage_word_check_url = sprintf(Config::get('garbage_word_check_url'), $access_token);

		$postData = '{ "content":"'. $data['group_name'] .'" }';

		$res = json_decode(sendCmd($garbage_word_check_url,$postData));

		if($res->errcode != 0){
			 return ['message'=>'团队名称含有敏感词汇','code'=>2500];
		}

		//敏感内容检测END

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//团队名称重复检查
		if($groupModel->where(['status'=>1,'group_name'=>$data['group_name']])->find()){
			return ['message'=>'团队名称重复','code'=>2600];
		}


		//活动参与计算步数的时间
		$today = date("Y-m-d",time());

		//查询创建人是否存在
		if( !$creator = $userModel->where('openid',$data['openid'])->find() ){

				return ['message'=>'创建人不存在','code'=>3000];
		//查询活动是否存在且申请时间是否在活动截止时间之前
		}else if (!$activity = $activityModel->where(['id'=>$data['activity_id'],'status'=>1])->where('end_date','>=',"$today")->find()) {
				
				return ['message'=>'活动不存在或已经结束','code'=>3001];
		//查询申请人是否已经申请过或参加了该周期截止时间的该活动
		}else if( $is_create = $groupPersonsModel->where(['openid'=>$data['openid'],'activity_id'=>$activity['id']])->where('join_date','<=',$activity['end_date'])->find()){

				return ['message'=>'您已创建了团队或已在别人团队中','code'=>3002];
		}else{
				
				// 开启事务
		        Db::startTrans();
		        try {	

		        	   $group_info = [
		        	   		'activity_id' => $data['activity_id'],
		        	   		'group_name' => $data['group_name'],
		        	   		'creator_openid' =>$data['openid'],
		        	   		'nums' => 1,
		        	   		'create_date' => $today,
		        	   		'create_time' => time()
		        	   ];

		        	   $groupModel->insert($group_info);
		        	   $group_id = $groupModel->getLastInsID();

		        	   $group_id = $groupModel->getLastInsID();

		        	   $group_persons_info = [
		        	   		'activity_id' => $data['activity_id'],
		        	   		'group_id' => $group_id,
		        	   		'openid' => $data['openid'],
		        	   		'nickname' => $creator['nickname'],
		        	   		'avatar' => $creator['avatar'],
		        	   		'join_date' => $today,
		        	   		'proportion' => 0,
		        	   		'create_time' => time()
		        	   ];
	
		        	   $groupPersonsModel->insert($group_persons_info);

		       		   Db::commit();

					   return ['message'=>'创建团队成功','code'=>3100,'my_group'=>$group_id];

				  	} catch (\Exception $e) {

			            Db::rollback();
			           
			            throw new \Exception("系统繁忙");

		          	}
		}

	}



	/**
	 * 加入团队
	 * @return boolen
	 */
	public function join_group()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Group/join_group.html?openid=oFGa94rlE20Dam4W_eUFWOYDNJ5A&activity_id=5&group_id=12;	
		require_params('openid','activity_id','group_id');  //activity_id为参与的活动id   group_id为加入组id
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//今天的时间
		$today = date("Y-m-d",time());

		//查询创建人是否存在
		if( !$user = $userModel->where('openid',$data['openid'])->find() ){

				return ['message'=>'申请加入人不存在','code'=>4000];
		//查询活动是否存在且申请时间是否在活动截止时间之前
		}else if (!$activity = $activityModel->where(['id'=>$data['activity_id'],'status'=>1])->where('end_date','>=',"$today")->find()) {
				
				return ['message'=>'活动不存在或已经结束','code'=>4001];
		//查询申请人是否已经申请过或参加了该周期截止时间前的活动
		}else if( $is_create = $groupPersonsModel->where(['openid'=>$data['openid'],'activity_id'=>$data['activity_id']])->where('join_date','<=',$activity['end_date'])->find()){

				return ['message'=>'您已创建了团队或已加入了别人的团队','code'=>4002];
		//判断该申请加入的团队是否存在 且获取团队信息	
		}else if(!$group_info = $groupModel->where('id',$data['group_id'])->find()){

				return ['message'=>'您申请加入的团队不存在','code'=>4003];
		}else{
			  //判断团队人数是否达到配置上限
			  if( $group_info['nums'] >= $activity['limit_persons'] ){

			  		return ['message'=>'该团已达到人数上限','code'=>4005];	
			  }

			  //可以加入团队情况
			  // 开启事务
        	Db::startTrans();
        	try {		
        				//该团队人数+1
        			   $groupModel->where('id',$data['group_id'])->setInc('nums');

        			   //插入团队人员信息
		        	   $group_persons_info = [
		        	   		'activity_id' => $data['activity_id'],
		        	   		'group_id' => $data['group_id'],
		        	   		'openid' => $data['openid'],
		        	   		'nickname' => $user['nickname'],
		        	   		'avatar' => $user['avatar'],
		        	   		'join_date' => $today,
		        	   		'proportion' => '0%',
		        	   		'create_time' => time()
		        	   ];

		        	   $groupPersonsModel->insert($group_persons_info);


		       		   Db::commit();

					   return ['message'=>'申请加入团队成功','code'=>4100];

			  	} catch (\Exception $e) {

		            Db::rollback();
		           
		            throw new \Exception("系统繁忙");

	          	}
	            


		}


	}

	/**
	 * 退出团队
	 * @return boolen
	 */
	public function leave_group()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Group/leave_group.html?openid=oFGa94rlE20Dam4W_eUFWOYDNJ5A&group_id=12;	
		require_params('openid','group_id');  //  group_id为加入组id
		$data = Request::param();

		$groupModel = new GroupModel;

		$groupPersonsModel = new GroupPersonsModel;

		//查询退出团队的人是否进入了该团队
		if(!$is_grouper = $groupPersonsModel->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->find()){

				return ['message'=>'您无权退出非您所在的团队','code'=>5000];


		}else{
			//通过上述判断后
			//1.团队总步数上减去其捐献步数 人数减1
	
			$groupModel->where(['id'=>$data['group_id'],'status'=>1])->setDec('nums');
			$groupModel->where(['id'=>$data['group_id'],'status'=>1])->setDec('group_steps',$is_grouper['contribute_steps']);

			// 2.删除其团队信息 
			$groupPersonsModel->where('id',$is_grouper['id'])->delete();
			return ['message'=>'成功退出团队','code'=>5100];
			//3.若退出团队的人为团队创始人，将其团队创始人creator_openid 设为0 暂时不用做(无太大意义)
			
		}

	}


	

	/**
	 * 捐献步数
	 * @return boolen
	 */
	public function contribute_steps()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Group/contribute_steps.html?openid=1&encryptedData=1&iv=1&group_id=8;	
		
		require_params('openid','encryptedData','iv','group_id','activity_id');  //group_id为加入组的id
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//今天的时间
		$today = date("Y-m-d",time());
		//核实捐献步数的时间是否在活动开始之后 结束之前
	   if(!$activity = $activityModel->where(['id'=>$data['activity_id'],'status'=>1])->where('start_date','<=',"$today")->where('end_date','>=',"$today")->find()){

	   		return ['message'=>'活动周期还未开始或已结束，不能捐献步数','code'=>6000];

	   }else if(!$step_info = $this->decryptedData($data['openid'],$data['encryptedData'],$data['iv'])){

	   		return ['message'=>'解密用户步数数据失败','code'=>6001];
	   }else{

	   		//获取该用户的加入团队时间
	   		if(!$join_date = $groupPersonsModel->field('join_date')->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->find()){

	   				return ['message'=>'未找到该用户加入团队的时间','code'=>6002];
	   		}else{
	   			//比较活动开始的时间与玩家加入该团队的时间
	   			$start_date_timestamp = strtotime($activity['start_date']);
	   			$join_date_timestamp = strtotime($join_date['join_date']);
	   			//实际计算时间
	   			$count_timestamp = 0;
	   			//目前玩家自己需要捐献的步数
	   			$contribute_steps = 0;

	   			//目前需要捐献给团队的步数
	   			$group_contribute_steps = 0;

	   			//如果玩家加入该团队的时间早于活动开始时间，将活动开始时间赋给实际计算步数时间
	   			if($start_date_timestamp>=$join_date_timestamp){
	   				$count_timestamp = $start_date_timestamp;
	   			}else{
	   				$count_timestamp = $join_date_timestamp;
	   			}

	   			foreach ($step_info as $k => $v) {
	   				if($v['timestamp'] >= $count_timestamp){
	   						$contribute_steps += $v['step'];
	   				}
	   			}
	   			
	       

		   		// 开启事务
	        	Db::startTrans();
	        	try {	
	        		//查看该玩家之前捐献的步数,若有,则需要减去之前已经捐献过的步数再处理
	        		$history_contributed_steps = $groupPersonsModel->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->find();

	        		if($history_contributed_steps['contribute_steps'] == $contribute_steps){
	        			return ['message'=>'请勿重复捐献步数','code'=>6200];
	        		}else{
	        			$group_contribute_steps = $contribute_steps - $history_contributed_steps['contribute_steps'];
	        		}

	        		//该团队总步数增加
        			$groupModel->where('id',$data['group_id'])->setInc('group_steps',$group_contribute_steps);

        			//更新该个人的贡献步数值
        			$update_data = [
        				'contribute_steps' => $contribute_steps,
        				'contribute_time' => time()
        			];

        			$groupPersonsModel->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->update($update_data);

	        		Db::commit();

					return ['message'=>'捐献步数成功','code'=>6100];

	        	} catch (\Exception $e) {

		            Db::rollback();
		           
		            throw new \Exception("系统繁忙");

	          	}



	   		}

	   }



	}



	 /**
   * 解密步数数据
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
      		//返回所有的步数信息
      		//获取返回的解密步数数组的最后7个(7天)数组信息
			return array_slice($dataObj['stepInfoList'],-7);
	  }else{

	  	   return false;
	  }

    }


}