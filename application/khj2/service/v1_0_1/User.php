<?php

namespace app\khj2\service\v1_0_1;

use think\Db;
use think\facade\Cache;
use think\facade\Config;
use app\khj2\model\User as UserModel;
use app\khj2\model\Video as VideoModel;
use app\khj2\model\Config as ConfigModel;
use app\khj2\model\UserRecord as UserRecordModel;

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
	public function login($code,$invite_id)
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
					$user->invite_id = $invite_id?$invite_id:0;
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
			$userRecord = UserModel::where('id', $data['user_id'])->field("free_used,invite_times")->find();
			if($data['is_play']==1){
				if($userRecord['free_used']==0){
					$userRecord->free_used = 1;
					$userRecord->save();
				}elseif($userRecord['invite_times']>0){
					$userRecord->invite_times = ['dec', 1];
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
			return true;
		} catch (\Exception $e) {
			trace($e->getMessage(),'error');
			return false;
		}
	}
}
