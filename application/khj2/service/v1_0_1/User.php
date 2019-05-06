<?php

namespace app\khj2\service\v1_0_1;

use think\Db;
use think\facade\Config;
use app\khj2\model\User as UserModel;
use app\khj2\model\Video as VideoModel;
use app\khj2\model\Config as ConfigModel;
use app\khj2\model\UserRecord as UserRecordModel;
use app\khj2\model\ChallengeLog as ChallengeLogModel;
use app\khj2\model\Sign as SignModel;
use app\khj2\model\Shop as ShopModel;

use zhise\HttpClient;

/**
 * 用户服务类
 */
class User
{

	private static $link = '';
	/**
	 * 用户登录
	 * @return array
	 */
	public function login($code,$invite_id,$is_fixed)
	{
		$appid = Config::get('wx_appid');
		$secret = Config::get('wx_secret');
		$loginUrl = Config::get('wx_login_url');
		$game_id = Config::get('game_id');


		$test = 0;
		if($test==1){
			$data['openid'] = 1;
			$data['session_key'] = 1;
		}else{
			try{
				$data = json_decode(file_get_contents(sprintf($loginUrl, $appid, $secret, $code)), true);
			} catch (\Exception $e) {
				$result = ['errcode' => 1,'errmsg'=>$e->getMessage()];
				return $result;
			}
		}


		$result = [];
		if (isset($data['openid'])) {
			$user = UserModel::where('openid', $data['openid'])->find();

			// 开启事务
			Db::startTrans();
			try {

				$time = time();
				if (!empty($user)) {
					$user->update_time = $time;
					$user->session_key = $data['session_key'];
					//$user->invite_id = $invite_id?$invite_id:0;
					$user->save();
				} else {
					$user = new UserModel();
					$user->update_time = $time;
					$user->openid = $data['openid'];
					$user->create_time = $time;
					$user->session_key = $data['session_key'];
					$user->invite_id = $invite_id?$invite_id:0;
					if($invite_id>0){
						$user->invite_linked = $this->get_invite_id($invite_id);
					}
					$user->save();
					//新用户初始化金币的值
					$userRecord = new UserRecordModel();
					$userRecord->user_id = $user->id;
					$userRecord->openid = $data['openid'];
					$userRecord->gold = 0;
					$userRecord->last_login = $time;
					$userRecord->user_status = 1;

					$userRecord->save();

					if($invite_id>0){
						$user_record = UserModel::where('id', $invite_id)->find();
						if ($user_record) {
							$user_record->invite_times       = ['inc', 1];
							$user_record->save();
						}
						$is_fixed = 0;//补卡功能暂停
						if($is_fixed>0 && $user_record['sign_type']>0 && $user_record['sign_left']>0){
							$dday = date("ymd");
							$preday = date("ymd",strtotime("-1 day"));
							$preday2 = date("ymd",strtotime("-2 day"));
							$p1 = SignModel::where('user_id',$invite_id)->where("dday",$preday)->find();
							if(empty($p1)){
								$p2 = SignModel::where('user_id',$invite_id)->where("dday",$preday2)->find();
								if(!empty($p2)){
									$sm = new SignModel();	
									$sm->ext = 1;
									$sm->linked = $p2['linked']+1;
									$sm->user_id = $invite_id;
									$sm->dday = $preday;
									$sm->addtime = time();
									$sm->save();
									$p = SignModel::where('user_id',$invite_id)->where("dday",$dday)->find();
									if(!empty($p)){
										$p->linked = $p2['linked']+2;
										$p->save();
									}
								}
							}
						}
					}
				}
				Db::commit();
			} catch (\Exception $e) {
				Db::rollback();
				$result = ['errcode' => 1,'errmsg'=>$e->getMessage()];
				trace("login error ".$e->getMessage(),'error');
				return $result;
			}

			$record = UserRecordModel::where('user_id', $user->id)->find();

			$result = [
				'status' => 1,
				'user_id' => $user->id,
				'last_login' => $time,
				'openid' => $data['openid'],
				'showid' => $data['openid']."-".$game_id,
				'user_status' => 1,
				];
		} else {
			trace("login error ".json_encode($data),'error');
			$result = ['errcode' => 1,'errmsg'=>'请求微信服务器失败'];
		}

		return $result;
	}

	private function get_invite_id($id){
                //static $link = '';
                $info = UserModel::where("id",$id)->field("invite_id")->find();
		$arr = explode('|',self::$link);
		if(in_array($id,$arr)){
			return false;
		}
                if(self::$link==''){
                        self::$link= $id;
                }else{
                        self::$link= $id."|".self::$link;
                }
		if(!empty($info['invite_linked'])){
			self::$link = $info['invite_linked']."|".self::$link;
		}elseif($info['invite_id']>0){
                        $this->get_invite_id($info['invite_id']);
                }
                return self::$link;
        }
	/**
	 * 更新用户信息
	 * @return void
	 */
	public function update($data)
	{
		// 开启事务
		Db::startTrans();
		try {
			$time = time();
			$userModel = new UserModel();
			$user = $userModel->where('id', $data['user_id'])->find();
			if(empty($user)){
				Db::rollback();
				trace($userModel->getLastSql(),'error');
				return ['errcode'=>1,'errmsg' => '用户不存在'];
			}
			//dump($user);die;
			$user->nickname = $data['nickname'];
			$user->avatar = $data['avatar'];
			$user->gender = $data['gender'];
			$user->update_time = $time;
			//$user->userRecord->nickname = $data['nickname'];
			//$user->userRecord->avatar = $data['avatar'];
			//$user->userRecord->update_time = $time;
			//$user->userRecord->gender = $data['gender'];

			$user->save();
			//$user->userRecord->save();

			Db::commit();

			//$user_status = $user->userRecord->user_status;

			return ['errcode'=>0,'user_status' => 1];
		} catch (\Exception $e) {
			Db::rollback();
			return ['errcode'=>1,'errmsg' => $e->getMessage()];
		}
	}


	/**
	 * @param  array $data 请求数据
	 * @return boolean
	 */
	public function friend($data)
	{
		if(empty($data['user_id'])){
			return ['errcode' => 1,'errmsg'=>'用户ID不能为空'];
		}
		try {
			$userRecord = UserModel::where('invite_id', $data['user_id'])->field("nickname,avatar")->select();
			return $userRecord;
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			throw new \Exception('系统繁忙');
		}
	}
	/**
	 * @param  array $data 请求数据
	 * @return boolean
	 */
	public function playtimes($data)
	{
		$result = [];
		$result['errcode'] = 0;
		try {
			$userRecord = UserModel::where('id', $data['user_id'])->field("free_used,invite_times,share_times")->find();
			if($data['is_play']==1){
				if($userRecord['free_used']==0){
					$userRecord->free_used = 1;
					$userRecord->save();
				}elseif($userRecord['invite_times']>0){
					$userRecord->invite_times = ['dec', 1];
					$userRecord->save();
				}elseif($userRecord['share_times']>0){
					$userRecord->share_times = ['dec', 1];
					$userRecord->save();
				}else{
					$result['errmsg'] = "次数不足";
					$result['errcode'] = 1;
				}
			}else{
				$result = $userRecord;
				$result['invite_count'] = $userRecord = UserModel::where('invite_id', $data['user_id'])->count();
			}
			return $result;
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			$result['errmsg'] =  $e->getMessage();
			$result['errcode'] = 1;
			return $result;
		}
	}
	/**
	 * @param  array $data 请求数据
	 * @return boolean
	 */
	public function broadcast()
	{
		try {
			$where = [];
			//$where['index'] = 'broadcast';
			$userRecord = ConfigModel::where($where)->column('value','index');
			$userRecord['broadcast'] = json_decode($userRecord['broadcast']);
			return $userRecord;
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			throw new \Exception('系统繁忙');
		}
	}
	//记录玩家行为
	public function video($data)
	{
		try {
			$m = new VideoModel();
			$m->ad_id = $data['ad_id'];
			$m->user_id = $data['user_id'];
			$m->is_end = $data['is_end'];
			$m->create_time = time();
			$m->save();
			if($data['is_end']==1){
				$where = [];
				$where['index'] = 'video_score';
				$c = ConfigModel::where($where)->find();
				if(!empty($c)){
					$u = UserModel::where('id', $data['user_id'])->field("openid,invite_id,invite_gold_uid")->find();
					if($u['invite_id']>0){
						$ui = UserModel::where('id', $u['invite_id'])->field("openid")->find();
					}
					$shop_data = [];
					$game_id = Config::get('game_id');
					$shop_data['openid'] = $u['openid'];
					$shop_data['invite_gold_uid'] = $u['invite_gold_uid'];
					$shop_data['invite_openid'] = isset($ui['openid'])?$ui['openid']:'';
					$shop_data['game_id'] = $game_id;
					$shop_data['score'] = $c['value'];
					$shop = new ShopModel();
					$shop->add_score($shop_data);
				}
			}
			return true;
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			return false;
		}
	}
	/**
	 * @param  array $data 请求数据
	 * @return boolean
	 */
	public function update_free_used()
	{
		try {
			$m = new UserModel();
			$res = $m->execute("update t_user set free_used=0 where 1");
			var_dump($res);
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			var_dump($e->getMessage());
		}
	}
	public function sign_type($data)
	{
		try {
			if($data['sign_type']>0){
				$m = UserModel::where('id', $data['user_id'])->find();
				if($m['sign_date']>0 && $m['sign_result']==0){
					$result['errcode'] = 1;
					$result['errmsg'] = '打卡任务正在进行中，不能重新绑定';
					return $result;
				}
				$m->sign_type = $data['sign_type'];
				$m->sign_date = date('ymd');
				$m->sign_result = 0;
				$m->sign_days = 0;
				$m->sign_left = 3;
				$m->save();
				$start = strtotime(date('Y-m-d').' 00:00:00');
				$end = strtotime(date('Y-m-d').' 23:59:59');
				$result = ChallengeLogModel::where('user_id',$data['user_id'])->where("start_time","between",[$start,$end])->count();
				if($result>2){
					$dday = date('ymd');
					$sm = SignModel::where('user_id', $data['user_id'])->where('dday',$dday)->find();
					if(empty($sm)){
						$m->sign_days = 1;
						$m->save();
						$sm = new SignModel();	
						$sm->ext = 0;
						$sm->linked = 1;
						$sm->user_id = $data['user_id'];
						$sm->dday = $dday;
						$sm->addtime = time();
						$sm->save();
					}
				}
				return true;
			}else{
				return false;
			}
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			return false;
		}
	}
	public function sign_info($data)
	{
		try {
			$m = UserModel::where('id', $data['user_id'])->find();
			if(empty($m)){
				return false;
			}
			$result = [];
			$result['userinfo'] = $m;
			$sm = SignModel::where('user_id', $data['user_id'])->order("dday desc")->limit(10)->select();
			$result['sign'] = $sm;
			$start = strtotime(date('Y-m-d').' 00:00:00');
			$end = strtotime(date('Y-m-d').' 23:59:59');
			$result['game_times'] = ChallengeLogModel::where('user_id',$data['user_id'])->where("start_time","between",[$start,$end])->count();
			return $result;
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			return false;
		}
	}
	public function sign_up($data){
		$user_id = $data['user_id'];
		$m = UserModel::where('id', $data['user_id'])->find();
		$m->update_time = time();
		$m->save();
		if($m['sign_days']>=180){
			$result['errcode'] = "1";
			$result['errmsg'] = "打卡任务已经完成";
			return $result;
		}
		if($m['sign_date']==date('ymd')){
			$result['errcode'] = "1";
			$result['errmsg'] = "今日重新绑定打卡方式不能补卡";
			return $result;
		}
		if($m['sign_type']>0 && $m['sign_left']>0){
			$preday = date("ymd",strtotime("-3 day"));
			if($preday<=$m['sign_date']){
				$preday = $m['sign_date'];
			}else{
				$failday = date("ymd",strtotime("-4 day"));
				$fail = SignModel::where('user_id',$user_id)->where("dday",$failday)->find();
				if(empty($fail)){
					$m->sign_result = 1;
					$m->save();
					$result['errcode'] = "1";
					$result['errmsg'] = "打卡失败";
					return $result;
				}
			}
			$p1 = SignModel::where('user_id',$user_id)->where("dday",">=",$preday)->order("dday asc")->select();
			$sign_days = 0;
			$signed = [];
			foreach($p1 as $k=>$v){
				if($v['dday']==date('ymd')){
					continue;
				}else{
					$signed[] = $v['dday'];
				}
			}
			$add = 0;
			for($i=3;$i>0;$i--){
				$day = date("ymd",strtotime("-".$i." day"));
				if(!in_array($day,$signed)){
					if($day>=$preday){
						$sm = new SignModel();	
						$sm->ext = 1;
						$sm->linked = 1;
						$sm->user_id = $user_id;
						$sm->dday = $day;
						$sm->addtime = time();
						$sm->save();
						$add++;
					}
				}
			}
			if($add>0){
				$m->sign_days = $m->sign_days + $add;
				$m->sign_left = $m->sign_left - $add;
				$m->save();
				return true;
			}else{
				return false;
			}
		}else{
			$result['errcode'] = "1";
			$result['errmsg'] = "未绑定打卡方式或没有补卡机会";
			if($m['sign_left']==0){
				$m->sign_result = 1;
				$m->save();
			}
			return $result;
		}
	}
	public function test(){
		/*
		   set_time_limit(0);
		   $list = UserModel::where("invite_id",'>',0)->field("id,invite_id")->order("id asc")->limit("40000,5000")->select();
		   foreach($list as $v){
			if($v['invite_id']>0){
				$r = $this->get_invite_id($v['invite_id']);
				self::$link = '';
				UserModel::where("id",$v['id'])->update(['invite_linked'=>$r]);
				echo $v['id']."---===".$r;
				echo "\r\n";
			}
		}
*/
/*
		$id = 4;
		$r = $this->get_invite_id($id);
		print_r($r);
		die;
*/
		$shop_data = [];
		$game_id = 1;
		$shop_data['openid'] = 'o_dT15ZuFuSJ7PFEdcfTPzj-1808';
		$shop_data['openid'] = 'o_dT15Q7sgRnxw68o7mpBt1Iz0gg';
		//$shop_data['openid'] = 'o_dT15azAN92OzVes6eX3xni_ATc';
		$shop_data['invite_gold_uid'] = 4;
		//$shop_data['invite_openid'] = 'o_dT15fPADuVVXIAUUcMCayQdDc8';
		$shop_data['invite_openid'] = 'o_dT15ZuFuSJ7PFEdcfTPzj-1808';
		$shop_data['game_id'] = $game_id;
		$shop_data['score'] = 100;
		$shop = new ShopModel();
		$result = $shop->add_score($shop_data);
		return $result;
	}
}
