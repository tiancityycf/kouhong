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
					$user->openid = $data['openid'];
					$user->create_time = $time;
					$user->session_key = $data['session_key'];
					$user->invite_id = $invite_id?$invite_id:0;
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
					$u = UserModel::where('id', $data['user_id'])->find();
					$shop_data = [];
					$game_id = Config::get('game_id');
					$shop_data['openid'] = $u['openid'];
					$shop_data['game_id'] = $game_id;
					$shop_data['score'] = $c['value'];
					//ShopModel::add_score($shop_data);
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
				$m->sign_type = $data['sign_type'];
				$m->sign_date = date('ymd');
				$m->save();
				$start = strtotime(date('Y-m-d').' 00:00:00');
				$end = strtotime(date('Y-m-d').' 23:59:59');
				$result = ChallengeLogModel::where('user_id',$data['user_id'])->where("start_time","between",[$start,$end])->count();
				if($result>2){
					$dday = date('ymd');
					$sm = SignModel::where('user_id', $data['user_id'])->where('dday',$dday)->find();
					if(empty($sm)){
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
	public function test(){
		$shop_data = [];
		$game_id = 1;
		$shop_data['openid'] = 'o_dT15ZuFuSJ7PFEdcfTPzj-1808';
		$shop_data['game_id'] = $game_id;
		$shop_data['score'] = 1.5;
		$shop = new ShopModel();
		$result = $shop->add_score($shop_data);
		print_r($result);
	}
}
