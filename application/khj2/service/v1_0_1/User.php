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
		$gameid = Config::get('game_id');


		try{
			$data = json_decode(file_get_contents(sprintf($loginUrl, $appid, $secret, $code)), true);
		} catch (\Exception $e) {
			$result = ['status' => 0];
			return $result;
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
					$user->userRecord->last_login = $time;
					$user->userRecord->save();
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
				$result = ['status' => 0];
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
			$result = ['status' => 0];
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
			$user = $userModel->where('openid', $data['openid'])->find();
			if(empty($user)){
				Db::rollback();
				trace($userModel->getLastSql(),'error');
				return ['error' => '用户不存在'];
			}
			//dump($user);die;
			$user->nickname = $data['nickname'];
			$user->avatar = $data['avatar'];
			$user->gender = $data['gender'];
			$user->update_time = $time;
			$user->userRecord->nickname = $data['nickname'];
			$user->userRecord->avatar = $data['avatar'];
			$user->userRecord->update_time = $time;
			$user->userRecord->gender = $data['gender'];

			$user->save();
			$user->userRecord->save();

			Db::commit();

			$user_status = $user->userRecord->user_status;

			return ['user_status' => $user_status];
		} catch (\Exception $e) {
			Db::rollback();
			return ['error' => $e->getMessage()];
		}
	}


	/**
	 * @param  array $data 请求数据
	 * @return boolean
	 */
	public function friend($data)
	{
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
			$where['index'] = 'broadcast';
			$userRecord = ConfigModel::where($where)->find();
			$userRecord['value'] = json_decode($userRecord['value']);
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
