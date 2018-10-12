<?php

namespace app\bxdj\controller\api\v1_0_1;

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

        //需要计算的用户的步数信息
        $result['step_info'] = $step_info['step'];


        //判断当天用户是否兑换过  !若用户当天进行了多次兑换 该步数信息会减去之前兑换的步数信息

        $today = date('Y-m-d',time());

        $today_steps = Db::name('step_coin_log')->where(['openid'=>$data['openid'],'exchange_date'=>$today])->sum('steps');

        if($today_steps){
        	$result['step_info'] = $result['step_info'] - $today_steps;
        }else{
        	$today_steps = 0;
        }
        
		/*        
		$exchange_history = Db::name('step_coin_log')->where(['openid'=>$data['openid'],'exchange_date'=>$today])->select();

        if(!empty($exchange_history)){
        	//!若用户当天进行了多次兑换 该步数信息会减去之前兑换的步数信息
        	$today_steps = 0;
        	//对一维数组或多维数组的判断
        	if(count($exchange_history)==count($exchange_history,1)){
 
        		 $today_steps = $exchange_history['steps'];   //$today_steps为当天历史兑换的总步数

        	}else{

        		foreach ($exchange_history as $k => $v) {
        		 $today_steps += $v['steps'];   //$today_steps为当天历史兑换的总步数
        		}
        	}
		    
		    $result['step_info'] = $result['step_info'] - $today_steps;
        }
        */

         //判断当天用户是否兑换过END

        //查询其当天未兑换步数应该的兑换比例；比例使用的是昨天与昨天之前的成功邀请的玩家的数量*配置比例
    	//获取缓存信息
		$config = Cache::get(config('config_key'));

		//获取配置的邀请新玩家数量—增加步数的比例	
		$config_rate = $config['nums_to_rate']['value'];

      	$endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
      	$cache_data = Cache::get($data['openid']);
      
      	//先查询缓存数据 若无，再查询数据库数据
      	if(!empty($cache_data) && $cache_data['endYesterday'] == $endYesterday){

      			//$result['step_info'] = ceil($result['step_info'] * $cache_data['exchange_step_rate']);

      			$jcjl = ceil($result['step_info'] * $cache_data['exchange_step_rate']) - $result['step_info'];
      			// echo $cache_data['exchange_step_rate'].'<br>';
      			// echo $jcjl;
      			//如果加成奖励大于0  生成加成奖励气泡
      			if($jcjl>0){
      				//如果有未点击兑换的加成奖励气泡，则不生成新的加成奖励气泡
      				$has_jcjl = Db::name('step')->where(['openid'=>$data['openid'],'comment'=>'加成奖励'])->where('status','in','0,2')->find();
      				//dump($has_jcjl);die;
      				if(!$has_jcjl){
				      	$insert_data = [
							'openid'=>$data['openid'],
							'steps'=>$jcjl,
							'create_time' => time(),
							'comment' => '加成奖励',
							'jcjl_log' => $cache_data['exchange_step_rate'] . '&' . $step_info['step'] . '&' . $today_steps
						];

						Db::name('step')->insert($insert_data);
      				}else{
      					//判断新的奖励步数是否不等于原纪录的奖励步数 若不等于且状态码是2的话（准备兑换的话），让其重新点击新的加成步数
      					if($has_jcjl['steps']!=$jcjl && $has_jcjl['status'] == 2){

	      					$update_data = [
								'steps'=>$jcjl,
								'create_time' => time(),
								'status' =>0,
								'jcjl_log' => $cache_data['exchange_step_rate'] . '&' . $step_info['step'] . '&' . $today_steps
							];

							Db::name('step')->where('id',$has_jcjl['id'])->update($update_data);
      						
      					}
      					
      				}
      				
      			}else{
      				$jcjl = 0;
      			}
      			//返回前端实际的兑换比例
      			$result['exchange_step_rate'] = ($cache_data['exchange_step_rate'] - 1)*100;

      	}else{
	      	$vali_invitees = Db::name('share_record')->where('share_openid',$data['openid'])->where('share_time','elt',$endYesterday)->select();
	      	//若有符合的邀请者
	      	if($vali_invitees){

	      		$nums = count($vali_invitees);

			    if($config_rate*$nums<=100){
			    	$exchange_step_rate = (100 + $config_rate*$nums)/100;
			    	//$result['step_info'] = ceil($result['step_info'] * (100 + $config_rate*$nums)/100);
			    	$jcjl = ceil($result['step_info'] * $exchange_step_rate) - $result['step_info'];
	      			//如果加成奖励大于0  生成加成奖励气泡
	      			if($jcjl>0){
	      				//如果有未点击兑换的加成奖励气泡，则不生成新的加成奖励气泡
	      				$has_jcjl = Db::name('step')->where(['openid'=>$data['openid'],'comment'=>'加成奖励'])->where('status','in','0,2')->find();
	      				//dump($has_jcjl);die;
	      				if(!$has_jcjl){
					      	$insert_data = [
								'openid'=>$data['openid'],
								'steps'=>$jcjl,
								'create_time' => time(),
								'comment' => '加成奖励',
								'jcjl_log' => $exchange_step_rate . '&' . $step_info['step'] . '&' . $today_steps
							];

							Db::name('step')->insert($insert_data);
	      				}else{
	      					//判断新的奖励步数是否不等于原纪录的奖励步数 若不等于且状态码是2的话（准备兑换的话），让其重新点击新的加成步数
	      					if($has_jcjl['steps']!=$jcjl && $has_jcjl['status'] == 2){

		      					$update_data = [
									'steps'=>$jcjl,
									'create_time' => time(),
									'status' =>0,
									'jcjl_log' => $exchange_step_rate . '&' . $step_info['step'] . '&' . $today_steps
								];

								Db::name('step')->where('id',$has_jcjl['id'])->update($update_data);
	      						
	      					}
	      					
	      				}
	      				
	      			}else{
	      				$jcjl = 0;
	      			}

			    	//将实际兑换比例存入缓存
			    	$cache_data = [
			    		'exchange_step_rate' => (100 + $config_rate*$nums)/100,
			    		'endYesterday' => $endYesterday
			    	];
			    	Cache::set($data['openid'],$cache_data);
			    	//返回前端实际的兑换比例
			    	$result['exchange_step_rate'] = $config_rate*$nums;
			    }else{

			    	$result['step_info'] = $result['step_info'] * 2;

			    	//将实际兑换比例存入缓存
			    	$cache_data = [
			    		'exchange_step_rate' => 2,
			    		'endYesterday' => $endYesterday
			    	];
			    	Cache::set($data['openid'],$cache_data);
			    	//返回前端实际的兑换比例
			    	$result['exchange_step_rate'] = 100;
			    }
			}else{
				   //若没有符合的邀请者
			    	$cache_data = [
			    		'exchange_step_rate' => 1,
			    		'endYesterday' => $endYesterday
			    	];
			    	Cache::set($data['openid'],$cache_data);
			    	//返回前端实际的兑换比例
			    	$result['exchange_step_rate'] = 0;

			}

      	}
	
      	//兑换步数对应的兑换比例END

      	//获取配置的邀请新玩家数量—增加步数的比例	(明天的兑换比例)
      	$now_invitees = Db::name('share_record')->where('share_openid',$data['openid'])->select();
     
	    $now_nums = count($now_invitees);

	    if($config_rate*$now_nums<=100){
	    	//返回前端明天的兑换比例
	    	$result['tomorrow_rate'] = $config_rate*$now_nums;
	    }else{
	    	//返回前端明天的兑换比例
	    	$result['tomorrow_rate'] = 100;
	    }
	    //明天的兑换比例END

	    //分享到群的水滴需要一直在首页面显示除非其到达了分享到群的限制上限
	    //判断用户分享到群次数是否达到每天限制
        $day = date('Y-m-d');
        $is_shared = Db::name('share_count')->where(['openid'=>$data['openid'],'share_day'=>$day])->select();

	    if(count($is_shared)>=$config['share_group_limit']['value']){
	    	$result['drops']['fxdq'] = 0;
	    }else{
	    	$result['drops']['fxdq'] = $config['share_group_getStep']['value'];
	    }

	    //删除昨天与昨天之前生成的或未兑换的邀请好友或加成奖励或签到成功水滴
		Db::name('step')->where('openid',$data['openid'])->where('status','in','0,2')->where('create_time','elt',$endYesterday)->delete();
	

        //邀请好友或加成奖励或签到成功，生成的水滴
        $reward_step = Db::name('step')->where(['openid'=>$data['openid'],'status'=>0])->select();

        if($reward_step){
        	$result['drops']['yqhy'] = 0;
        	$result['drops']['qdjl'] = 0;
        	$result['drops']['jcjl'] = 0;

        	foreach ($reward_step as $k1 => $v1) {
        		if($v1['comment'] == '邀请好友'){

        			$result['drops']['yqhy'] += $v1['steps'];
        		}else if($v1['comment'] == '签到奖励'){

        			$result['drops']['qdjl'] += $v1['steps'];

        		}else if($v1['comment'] == '加成奖励'){

        			$result['drops']['jcjl'] = $v1['steps'];
        		}
        	}
        	
        }
      	//生成水滴end

        //返回的$result['step_info']需要加上用户分享群或要求新用户或签到成功后的奖励步数
        //点击了生成的水滴 准备兑换步数
      	$prepare_step = Db::name('step')->where(['openid'=>$data['openid'],'status'=>2])->select();
      	if($prepare_step){
      		$prepareStep = 0;
        	foreach ($prepare_step as $k2 => $v2) {
        		$prepareStep += $v2['steps'];
        	}
        	
        	$result['step_info'] = $result['step_info'] + $prepareStep;
      	}

      	//准备兑换步数end

      	
        //查询玩家是否有成功邀请新玩家？ 被邀请者
        $invitee = Db::name('share_record')->where('share_openid',$data['openid'])->order('share_time desc')->limit(4)->select();
        if(!$invitee){
        	$result['invitee'] = [];
        }else{
        	$invitee_imgs = array();
        	foreach ($invitee as $k => $v) {
        		$avatar = Db::name('user')->where('openid',$v['click_openid'])->find();
        		$invitee_imgs[] = $avatar['avatar'];
        	}
        	$result['invitee'] = $invitee_imgs;
        }
        //查询玩家是否有成功邀请新玩家END
 
        //若玩家参与了活动且活动已经结束 返回对应参与活动的信息
        $groupModel = new \app\bxdj\model\Group();
        
        $group_info = $groupModel->alias('g')->join('t_group_persons a','g.id = a.group_id')->join('t_activity c','g.activity_id = c.id')->field('g.id,g.group_name,g.group_steps,g.rank,g.nums,g.status,a.contribute_steps,a.get_reward,a.proportion,a.is_click,c.title')->where(['a.openid'=>$data['openid'],'a.is_click'=>0,'g.status'=>0])->find();
       	
  		if (!$group_info){
  			 $result['group_rank'] = [];
  			 $result['group_info'] = [];
  		}else{

  			$group_rank = $groupModel->where(['status'=>0,'id'=>$group_info['id']])->field('group_name,group_steps')->limit(3)->order('group_steps desc')->select();

  			if($group_info['proportion']!=0){
  				$group_info['reward'] = intval($group_info['get_reward']/$group_info['proportion']);
  			}

  			$result['group_rank'] = $group_rank;
  			$result['group_info'] = $group_info;
  		}
  		//玩家参与了活动且活动已经结束END


  		//查询推出的新活动并在首页进行提醒
  		$activityModel = new \app\bxdj\model\Activity();
  		$new_activity = $activityModel->field('id,title,start_date,end_date')->where(['status'=>1])->find();

  		if($new_activity){

  			$is_click = Db::name('activity_click')->where(['activity_id'=>$new_activity['id'],'openid'=>$data['openid']])->find();
	  		//若找到了新活动且该新活动还未被该玩家点击过
	  		if($new_activity && !$is_click){
	  			$result['new_activity'] = $new_activity;
	  		}else{
	  			$result['new_activity'] = [];
	  		}

  		}else{

	  		$result['new_activity'] = [];
	  	}
  		

        return result(200, 'ok', $result);


	}

	/**
	 * 点击水滴后改变水滴状态为2
	 * @return boolen
	 */
	public function click_drops()
	{
		require_params('openid','type');  //type=1 '邀请好友';   type = 3  '签到奖励'
        $data = Request::param();

        if($data['type'] == 1){
           $res = Db::name('step')->where(['openid'=>$data['openid'],'status'=>0,'comment'=>'邀请好友'])->update(['status'=>2]);

        }else if($data['type'] == 3){
           $res = Db::name('step')->where(['openid'=>$data['openid'],'status'=>0,'comment'=>'签到奖励'])->update(['status'=>2]);
           
        }else if($data['type'] == 4){
        	$res = Db::name('step')->where(['openid'=>$data['openid'],'status'=>0,'comment'=>'加成奖励'])->update(['status'=>2]);
        }

        if($res != false){
        	return ['message'=>'ok','code'=>1001];
        }

        return ['message'=>'fail','code'=>1000];
        
	}

	/**
	 * 查看团队赛结束信息后设为已点击过状态 1
	 * @return boolen
	 */
	public function click_groupInfo()
	{
		require_params('openid','group_id');  //group_id 为团队id
        $data = Request::param();

        $groupPersonsModel = new \app\bxdj\model\GroupPersons();
        $groupPersonsModel->where(['openid' =>$data['openid'],'group_id'=>$data['group_id']])->update(['is_click'=>1]);
	}

	/**
	 * 查看团队赛结束信息后设为已点击过状态 1
	 * @return boolen
	 */
	public function click_activityInfo()
	{
		require_params('openid','activity_id');  //activity_id 为团队id
        $data = Request::param();
        $has_clicker = Db::name('activity_click')->where(['openid' =>$data['openid'],'activity_id'=>$data['activity_id']])->find();

        if($has_clicker){

        	return '您点击过了';

        }else{

        	 $insert_data = [
        		'openid' => $data['openid'],
        		'activity_id' => $data['activity_id'],
        		'click_time' => time()
        	];
        	Db::name('activity_click')->insert($insert_data);
        	return '更新成功';

        }
           
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/user/login.html?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
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
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/user/center.html?openid=1&sign=0a53bf188436d7372adfa7e613217f01&timestamp=1
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
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/user/exchang_coin.html?openid=1&sign=123&&timestamp=1&encryptedData=123&iv=123;
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

			        	// 开启事务
			            Db::startTrans();
			            try {
				        		//如果当天没有进行兑换
				        		//获取步数兑换燃力币的比例
						        $exchange_rate = $config['exchange_rate']['value'];

						        //兑换步数时需要加上用户分享群或要求新用户或签到成功后的奖励步数
						        $reward_step = Db::name('step')->where(['openid'=>$data['openid'],'status'=>2])->select();

						        $rewardStep = 0;
						        if($reward_step){
						    
						        	foreach ($reward_step as $k1 => $v1) {
						        		$rewardStep += $v1['steps'];
						        	}
						        	
						        }

								//查询其当天未兑换步数应该的兑换比例；比例使用的是昨天与昨天之前的成功邀请的玩家的数量*配置比例

									//直接插缓存中数据即可，在user/index接口有存入各用户的缓存兑换比例记录
							      	//$cache_data = Cache::get($data['openid']);
							      	//dump($cache_data);die;
					      			//$data['steps'] = ceil($data['steps'] * $cache_data['exchange_step_rate']);
								//查询步数兑换比例END

					      		//获取配置设置的每天兑换步数上限值 若超过上限值 默认其实际步数为上限值
			        			$exchange_step_limit = $config['exchange_step_limit']['value'];
					      		if ($data['steps']  >= $exchange_step_limit) {
					        		
					        		$data['steps'] = $exchange_step_limit;

			        			}
			        			//默认其兑换为上限值END
									
						        //目前兑换比例为6/10000； $data['steps']为用户实际步数x步数兑换比例值，$rewardStep为用户奖励步数
						        $coins = number_format(($data['steps']+$rewardStep) * $exchange_rate,4);

						        if($coins){
						        	//增加对应玩家的燃力币值
						        	Db::name('step_coin')->where('openid',$data['openid'])->setInc('coins',$coins);

						        	 $insert_data = [
						        	 		'openid' => $data['openid'],
						        	 		'steps'  => $data['steps'],
						        	 		'reward_steps' => $rewardStep,
						        	 		'get_coins' => $coins,
						        	 		'exchange_date' => date('Y-m-d',time()),
						        	 		'create_time' => time()
						        	 ];
						        	 //插入步数兑换燃力币的日志信息
						        	 Db::name('step_coin_log')->insert($insert_data);

						        	 //将用户分享群或要求新用户或签到成功后的奖励步数更新为已兑换的状态
						        	 Db::name('step')->where(['openid'=>$data['openid'],'status'=>2])->update(['status'=>1]);
						        }

					       		Db::commit();
					       		return result(200, '0k', ['step'=>0,'get_coins'=>$coins]);   

			            } catch (\Exception $e) {

			                Db::rollback();
			                throw new \Exception("系统繁忙");

			            }

			    //当天有兑换记录
			    }else{

			        	//如果当天进行兑换了 1.不许其超过每日兑换步数上限 2.兑换步数需要减去之前兑换的步数
			        	//获取配置设置的每天兑换步数上限值
			        	$exchange_step_limit = $config['exchange_step_limit']['value'];

			        	//将需要兑换的实际用户步数$data['steps']赋给$exchange_steps  初始化兑换步数值
			        	$exchange_steps = $data['steps'];
			        	//today_steps为今天兑换过的步数
			        	$today_steps = 0;

			        	//一维数组与多维数组的判断
			        	if(count($exchange_history)==count($exchange_history,1)){

			        		 $today_steps = $exchange_history['steps'];   //$today_steps为当天历史兑换的总步数

			        	}else{
			        		foreach ($exchange_history as $k => $v) {
			        		 $today_steps += $v['steps'];   //$today_steps为当天历史兑换的总步数
			        		}
			        	}

	        			//查询其当天未兑换步数应该的兑换比例；比例使用的是昨天与昨天之前的成功邀请的玩家的数量*配置比例

							//直接插缓存中数据即可，在user/index接口有存入各用户的缓存兑换比例记录
						      	//$cache_data = Cache::get($data['openid']);
						      	//dump($cache_data);die;
				      			//$exchange_steps = ceil($exchange_steps * $cache_data['exchange_step_rate']);

				      	//查询步数兑换比例END

			        	$exchange_steps = $exchange_steps - $today_steps;  //$exchange_steps为目前计算后需要兑换的步数

			        	//如果计算后需要兑换的步数与今天已经兑换的步数超过了exchange_step_limit(30000步)，则默认计算后需要兑换的步数为0步
			        	if( $exchange_steps + $today_steps >= $exchange_step_limit){
			        			$exchange_steps = 0;
			        	}

	        			//兑换步数时需要加上用户分享群或要求新用户或签到成功后的奖励步数
				        $reward_step = Db::name('step')->where(['openid'=>$data['openid'],'status'=>2])->select();

				        $rewardStep = 0;

				        if($reward_step){
				        	
				        	foreach ($reward_step as $k1 => $v1) {
				        		$rewardStep += $v1['steps'];
				        	}
				      
				        }

			        	//if ($exchange_steps + $rewardStep + $today_steps >= $exchange_step_limit) {
			        		//核实用户兑换步数值是否超过每日兑换步数上限
			        		//return result(200, '您本次兑换步数已经超过每日兑换步数上限', ['step'=>0,'get_coins'=>$coins]);

			        	//}
			        	//有当天新的需要兑换的步数
			        	if($exchange_steps + $rewardStep > 0){

				        	// 开启事务
				            Db::startTrans();
				            try {

			        			$exchange_rate = $config['exchange_rate']['value'];

						        //目前兑换比例为6/10000； $data['steps']为用户实际步数，$rewardStep为用户奖励步数
						        $coins = number_format(($exchange_steps+$rewardStep) * $exchange_rate,4);

						        if($coins){

						        	 Db::name('step_coin')->where('openid',$data['openid'])->setInc('coins',$coins);

						        	 $insert_data = [
						        	 		'openid' => $data['openid'],
						        	 		'steps'  => $exchange_steps,
						        	 		'reward_steps' => $rewardStep,
						        	 		'get_coins' => $coins,
						        	 		'exchange_date' => date('Y-m-d',time()),
						        	 		'create_time' => time()
						        	 ];

						        	 Db::name('step_coin_log')->insert($insert_data);

						        	 //将用户分享群或要求新用户或签到成功后的奖励步数更新为已兑换的状态
						        	 Db::name('step')->where(['openid'=>$data['openid'],'status'=>2])->update(['status'=>1]);
						        	 
						        }

						        Db::commit();
						        return result(200, '0k', ['step'=>0,'get_coins'=>$coins]);  

				            } catch (\Exception $e) {

				                Db::rollback();
				                throw new \Exception("系统繁忙");

				            }
					   //没有当天新的需要兑换的步数
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
      		//返回第三十天的步数信息
			return $dataObj['stepInfoList'][30];
	  }else{

	  	   return false;
	  }

    }



}