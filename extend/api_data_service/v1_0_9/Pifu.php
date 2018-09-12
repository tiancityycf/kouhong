<?php

namespace api_data_service\v1_0_9;

use think\facade\Config;
use api_data_service\Config as ConfigService;
use api_data_service\Share as ShareService;
use model\UserRecord as UserRecordModel;
use model\Pifu as PifuModel;
use model\PifuShareLog as PifuShareLogModel;
use model\UserPifu as UserPifuModel;
use model\UserPifuTotal as UserPifuTotalModel;

class Pifu
{
	public function pifuList($user_id)
	{
		$pifu_data = PifuModel::where('status', 1)->select();
		$userRecord = UserRecordModel::where('user_id', $user_id)->find();
		$user_pifu_total = UserPifuTotalModel::where('user_id', $user_id)->find();

		$pifu_list = [];
		if ($pifu_data) {
			foreach ($pifu_data as $key => $pifu) {
				$pifu_list[$key]['id'] = $pifu->id;
				$pifu_list[$key]['img'] = $pifu->img;
				$pifu_list[$key]['gold'] = $pifu->gold;
				$pifu_list[$key]['tiaojian'] = $pifu->tiaojian;
				$pifu_list[$key]['beijing'] = $pifu->beijing;
				$pifu_list[$key]['miaoshu'] = $pifu->miaoshu;
				$pifu_list[$key]['success_txt'] = $pifu->success_txt;
				$pifu_list[$key]['false_txt'] = $pifu->false_txt;
				$pifu_list[$key]['pifu_status'] = 0;  //未购买
				if ($userRecord->pifu_id == $pifu->id) {
					$pifu_list[$key]['pifu_status'] = 2;  //当前皮肤
				} else {
					if ($user_pifu_total && $user_pifu_total->pifu) {
						$pifu_arr = json_decode($user_pifu_total->pifu);

						if (in_array($pifu->id, $pifu_arr)) {
							$pifu_list[$key]['pifu_status'] = 1; //已经购买
						}
					}
				}
				
			}


		}

		return $pifu_list;
	}

	public function pifu_list($data){
		$user_id = $data['user_id'];
		return ['status' => 1, 'pifu_list' => $this->pifuList($user_id)];
	}

	public function selectPifu($data)
	{
		$user_id = $data['user_id'];
		$pifu_id = $data['pifu_id'];

		$pifu = PifuModel::where('id', $pifu_id)->find();
		$userRecord = UserRecordModel::where('user_id', $user_id)->find();

		if (!$userRecord || !$pifu) {
			return ['status' => 0, 'msg' => '系统错误！'];
		}

		$userRecord->pifu_id = $pifu_id;
		 if ($userRecord->save()) {
		 	$pifu_list = $this->pifuList($user_id);
		 	return ['status' => 1, 'pifu_list' => $pifu_list, 'pifu' => $pifu->img];
		 } else {
		 	return ['status' => 0];
		 }
	}


	public function buyPifu($data)
	{
		$user_id = $data['user_id'];
		$pifu_id = $data['pifu_id'];

		if (in_array($pifu_id, [14,15,16])) {
			return ['status' => 0, 'msg' => '系统错误！', 'pifu_id' => $pifu_id];
		}


		$userRecord = UserRecordModel::where('user_id', $user_id)->find();
		$pifu = PifuModel::where('id', $pifu_id)->find();

		if (!$userRecord || !$pifu) {
			return ['status' => 0, 'msg' => '系统错误！', 'pifu_id' => $pifu_id];
		}

		if ((int)$userRecord->gold < (int)$pifu->gold) {
			return ['status' => 2, 'msg' => '金币不足！', 'pifu_id' => $pifu_id];
		}

		$user_pifu = UserPifuModel::where('user_id', $user_id)->where('pifu_id', $pifu_id)->find();
		if ($user_pifu) {
			return ['status' => 2, 'msg' => '当前皮肤已经购买！', 'pifu_id' => $pifu_id];
		}

		$this->savePifu($user_id,$pifu_id);

		$userRecord->gold -= $pifu->gold;
		$userRecord->save();

		$pifu_list = $this->pifuList($user_id);
		return ['status' => 1, 'pifu_list' => $pifu_list, 'gold' => $userRecord->gold, 'pifu' => $pifu->img];
	}

	public function sharePifu($data)
	{
		$user_id = $data['user_id'];
		$pifu_id = $data['pifu_id'];
		$time = time();

		if ($pifu_id == 1) {
			$this->savePifu($user_id,$pifu_id);
			return ['status' => 1, 'is_new_group' => 1];
		}

		if (!in_array($pifu_id, [14,15,16])) {
			return ['status' => 0, 'msg' => '系统错误！'];
		}

		$errorCode = ShareService::decryptedData($user_id, $data['encryptedData'], $data['iv'], $result);

		$status = 0;   //接口状态 0 失败  1 成功
		$is_new_group = 0; // 是否分享到重复群  0 重复群  1 未重复
		if ($errorCode == 0) {
			$resultArr = json_decode($result, true);
			$group_id = $resultArr['openGId'];

			$old_log = PifuShareLogModel::where('user_id', $user_id)
				->where('gid', $group_id)
				->find();

			if (!$old_log) {
				$model = new PifuShareLogModel();
				$model->user_id = $user_id;
				$model->pifu_id = $pifu_id;
				$model->gid = $group_id;
				$model->create_time = $time;
				$model->save();

				if ($this->getLimit($user_id,$pifu_id)) {
					$this->savePifu($user_id,$pifu_id);
				}

				$is_new_group = 1;
			}

			$status = 1;
		}

		return ['status' => $status, 'is_new_group' => $is_new_group];
	}

	public function getLimit($user_id, $pifu_id)
	{
		$count = PifuShareLogModel::where('user_id', $user_id)->where('pifu_id', $pifu_id)->count();
		$pifu_share_num = ConfigService::get('pifu_share_num');

		if ($count >= $pifu_share_num) {
			return true;
		} else {
			return false;
		}
	}

	public function savePifu($user_id,$pifu_id)
	{
		$user_pifu = new UserPifuModel();
		$user_pifu->save([
			'user_id' => $user_id,
			'pifu_id' => $pifu_id,
			'create_time' => time(),
			'create_date' => date('ymd',time()),
		]);

		$user_pifu_total = UserPifuTotalModel::where('user_id', $user_id)->find();

		if (!$user_pifu_total) {
			$user_pifu_total = new UserPifuTotalModel();
			$pifu_arr = [];
			array_push($pifu_arr, $pifu_id);

			$user_pifu_total->save([
				'user_id' => $user_id,
				'pifu' => json_encode($pifu_arr),
			]);
		} else {
			$pifu_arr = json_decode($user_pifu_total->pifu);
			array_push($pifu_arr, $pifu_id);

			$user_pifu_total->save([
				'pifu' => json_encode($pifu_arr),
			]);
		}
	}

	public function pifuIfo($data)
	{
		$user_id = $data['user_id'];
		$pifu_id = $data['pifu_id'];

		if (!in_array($pifu_id, [14,15,16])) {
			return ['status' => 0, 'msg' => '系统错误！'];
		}

		$count = PifuShareLogModel::where('user_id', $user_id)->where('pifu_id', $pifu_id)->count();
		$pifu_share_num = ConfigService::get('pifu_share_num');

		return ['count' => $count, 'share_limit' => $pifu_share_num];
	}
}