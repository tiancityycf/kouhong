<?php

namespace api_data_service\v1_0_9_1;

use think\facade\Config;
use model\UserRecord as UserRecordModel;
use model\UserTili as UserTiliModel;
use api_data_service\Config as ConfigService;

class Tili
{
	public function tili($data)
	{
		$user_id = $data['user_id'];

		$time = time();
		$tili_time_jiange = ConfigService::get('tili_time_jiange');   // 获得体力的时间间隔
		$tili_limit = ConfigService::get('tili_limit');   // 体力上限

		$user_tili = UserTiliModel::where('user_id', $user_id)->find();
		$userRecord = UserRecordModel::where('user_id', $user_id)->find();

		if ($userRecord->chance_num >= $tili_limit) {
			return [
				'status' => 1,
				'time' => 0,
				'chance_num' => $userRecord->chance_num,
				'countdown' => 0,
				'tili_limit' => $tili_limit
			];
		} else {
			if (!$user_tili) {
				$user_tili = new UserTiliModel();
				$user_tili->save(['tili_time' => $time, 'user_id' => $user_id]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			}

			if ($user_tili->tili_time == 0) {
				$user_tili->save(['tili_time' => $time]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			}

			if (($time - $user_tili->tili_time) < $tili_time_jiange * 60) {
				return [
					'status' => 1,
					'time' => $tili_time_jiange * 60 - ($time - $user_tili->tili_time),
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			}

			$userRecord->chance_num += 1;
			$userRecord->save();

			if ($userRecord->chance_num < $tili_limit) {
				$user_tili->save(['tili_time' => $time]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			} else {
				$user_tili->save(['tili_time' => 0]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 0,
					'tili_limit' => $tili_limit
				];
			}


		}
	}
}