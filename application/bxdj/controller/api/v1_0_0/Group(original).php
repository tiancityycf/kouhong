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
	 * 团队首页 展示团队排名等
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Group/index.html?openid=1&activity_id=5;	
		require_params('openid','activity_id');  //activity_id为参与的活动id
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//活动参与计算步数的时间
		$today = date("Y-m-d",time());

		//查询用户是否存在
		if( !$user = $userModel->where('openid',$data['openid'])->find() ){

				return ['message'=>'创建人不存在','code'=>2000];
		//查询活动是否存在且申请时间是否在活动截止时间之前
		}else if (!$activity = $activityModel->where(['id'=>$data['activity_id'],'status'=>1])->where('end_date','>=',"$today")->find()) {
				
				return ['message'=>'活动不存在或已经结束','code'=>2001];

		}else{
			//若通过上述判断，1.展示目前团队的排行榜 2.判断该查看的玩家是创建团队逻辑还是查看自己团队的逻辑
			//1.展示目前团队的排行榜
			$groups = $groupModel->where('create_date','<=',$activity['end_date'])->limit(6)->order('group_steps desc')->select();
			//2.判断该查看的玩家是创建团队逻辑还是查看自己团队的逻辑
			$is_create = $groupPersonsModel->where(['openid'=>$data['openid']])->where('join_date','<=',$activity['end_date'])->find();

			if(!$is_create){
				//创建自己团队逻辑
				return ['message'=>'您可以创建自己团队','code'=>2002,'data'=>$groups,'my_group'=>0];

			}else{
				//查看自己团队的逻辑
				return ['message'=>'您已有团队,请查看','code'=>2003,'data'=>$groups,'my_group'=>$is_create['group_id']];
			}

		}

	}	

	/**
	 * 创建团队
	 * @return json
	 */
	public function create_group()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Group/create_group.html?openid=1&activity_id=5;
		require_params('openid','activity_id');  //activity_id为参与的活动id
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

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
		        	   $group_nums = $groupModel->where('create_date','<=',$activity['end_date'])->count()+1;

		        	   $group_info = [
		        	   		'activity_id' => $data['activity_id'],
		        	   		'group_name' => '步数PK团队'.$group_nums,
		        	   		'creator_openid' =>$data['openid'],
		        	   		'nums' => 1,
		        	   		'create_date' => $today,
		        	   		'create_time' => time()
		        	   ];

		        	   $groupModel->insert($group_info);

		        	   $group_id = $groupModel->getLastInsID();

		        	   $group_persons_info = [
		        	   		'activity_id' => $data['activity_id'],
		        	   		'group_id' => $group_id,
		        	   		'openid' => $data['openid'],
		        	   		'nickname' => $creator['nickname'],
		        	   		'avatar' => $creator['avatar'],
		        	   		'join_date' => $today,
		        	   		'proportion' => '0%',
		        	   		'create_time' => time()
		        	   ];
	
		        	   $groupPersonsModel->insert($group_persons_info);

		       		   Db::commit();

					   return ['message'=>'创建团队成功','code'=>3100];

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
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Group/join_group.html?openid=1&activity_id=5&group_id=8;	
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
		}else if( $is_create = $groupPersonsModel->where(['openid'=>$data['openid']])->where('join_date','<=',$activity['end_date'])->find()){

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

					   return ['message'=>'申请加入团队成功','code'=>3100];

			  	} catch (\Exception $e) {

		            Db::rollback();
		           
		            throw new \Exception("系统繁忙");

	          	}
	            


		}


	}


	/**
	 * 查看自己团队信息
	 * @return json
	 */
	public function check_group()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Group/check_group.html?openid=1&group_id=8;	
		require_params('openid','group_id');  // group_id为需要查看组的id
		$data = Request::param();

		$groupModel = new GroupModel;
		$groupPersonsModel = new GroupPersonsModel;

		//查询申请查看自己团队的人是否进入了该团队
		if( !$is_grouper = $groupPersonsModel->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->find()){

				return ['message'=>'您无权查看非您团队的信息','code'=>5000];

		}else{

			 //group_info为团队信息
			 $group_info = $groupModel->where('id',$data['group_id'])->find();

			 //group_persons_info为团队人员信息  其中数组第一位为团队的创始人
			 $group_persons_info = $groupPersonsModel->where('group_id',$data['group_id'])->select();

			 $return_data = [
			 		'group_info' => $group_info,
			 		'group_persons_info' => $group_persons_info
			 ];

			 return ['message'=>'ok','code'=>5001,'data'=>$return_data];
		}

	}


	/**
	 * 捐献步数
	 * @return boolen
	 */
	public function contribute_steps()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Group/contribute_steps.html?openid=1&encryptedData=1&iv=1&group_id=8;	
		
		require_params('openid','encryptedData','iv','group_id','activity_id');  //group_id为加入组的id
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//今天的时间
		$today = date("Y-m-d",time());
		//核实捐献步数的时间是否在活动开始之后
	   if(!$activity = $activityModel->where(['id'=>$data['activity_id'],'status'=>1])->where('start_date','<=',"$today")->find()){

	   		return ['message'=>'活动周期还未开始，不能捐献步数','code'=>6000];

	   }else if(!$step_info = $this->decryptedData($data['openid'],$data['encryptedData'],$data['iv'])){

	   		return ['message'=>'解密用户步数数据失败','code'=>6001];
	   }else{

	   		//获取该用户的加入团队时间
	   		if(!$join_date = $groupPersonsModel->field('join_date')->where(['openid'=>$data['openid'],'group_id'=>$data['group_id']])->find()){

	   				return ['message'=>'未找到该用户加入团队的时间','code'=>6002];
	   		}else{
	   			//比较活动开始的时间与玩家加入该团队的时间
	   			$start_date_timestamp = strtotime($activity['start_date']);
	   			$join_date_timestamp = strtotime($join_date);
	   			//实际计算时间
	   			$count_timestamp = 0;
	   			//捐献步数
	   			$contribute_steps = 0;

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

	        		//该团队总步数增加
        			$groupModel->where('id',$data['group_id'])->setInc('group_steps',$contribute_steps);

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