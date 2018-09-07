<?php

namespace api_data_service\v1_0_9_1;

use think\facade\Config;
use model\UserRecord as UserRecordModel;
use model\Pifu as PifuModel;
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
				if ($userRecord->pifu_id == $pifu->id) {
					$pifu_list[$key]['pifu_status'] = 2;
				} else {
					if ($user_pifu_total && $user_pifu_total->pifu) {
						$pifu_arr = json_decode($user_pifu_total->pifu);

						if (in_array($pifu->id, $pifu_arr)) {
							$pifu_list[$key]['pifu_status'] = 1;
						}
					} else {
						$pifu_list[$key]['pifu_status'] = 0;
					}
				}
				
			}


		}

		return ['status' => 1, 'pifu_list' => $pifu_list];
	}

	public function select($data)
	{
		$user_id = $data['user_id'];
		$pifu_id = $data['pifu_id'];

		$userRecord = UserRecordModel::where('user_id', $user_id)->find();
		$userRecord->pifu_id = $pifu_id;
		 if ($userRecord->save()) {
		 	$pifu_list = $this->pifuList($user_id);
		 	return ['status' => 1, 'pifu_list' => $pifu_list];
		 } else {
		 	return ['status' => 0];
		 }
	}


	public function buy($data)
	{
		$user_id = $data['user_id'];
		$pifu_id = $data['pifu_id'];

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

		$pifu_list = $this->pifuList($user_id);
		return ['status' => 1, 'pifu_list' => $pifu_list];
	}
}