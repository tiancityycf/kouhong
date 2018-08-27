<?php

namespace api_data_service\v1_0_8;

use think\Db;

use model\UserRecord as UserRecordModel;
use model\Suipian as SuipianModel;
use model\SuipianRecord as SuipianRecordModel;
use model\UserSuipian as UserSuipianModel;
use api_data_service\Config as ConfigService;

class UserSuipian
{
	public function suipian($user_id)
	{
		$num = (int)ConfigService::get('success_suipian_num');
		$time = time();
		$date = date('ymd', time());

		$rand_num = rand(1,$num);

		$suipian_type = $this->getSuipian($rand_num);
		if (!$suipian_type) {
			return 0;
		}

		// 开启事务
		Db::startTrans();
		try{
			$user_suipian = UserSuipianModel::where('user_id', $user_id)->find();
			if (!$user_suipian) {
				$user_suipian = new UserSuipianModel();
				$user_suipian->save([$suipian_type['index'] => 1, 'user_id' => $user_id]);
			} else {
				$user_suipian->save([$suipian_type['index'] => ['inc', 1]]);
			}

			$suipian_record = new SuipianRecordModel();
			$suipian_record ->save([
				'user_id' => $user_id,
				'suipian' => $suipian_type['id'],
				'create_time' => $time,
				'create_date' => $date,
			]);

			Db::commit();

			return $suipian_type['id'];
		} catch (\Exception $e) {
			Db::rollback();
			trace($e->getMessage(),'error');
			throw new \Exception($e->getMessage());
		}
	}


	protected function getSuipian($num)
	{
		$suipian_data = SuipianModel::select();

		$suipian = [];
		foreach ($suipian_data as $data) {
			$arr = explode('-', $data->percent);
			if ($num >= $arr[0]  && $num <= $arr[1]) {
				$suipian['index'] = $data->index;
				$suipian['id'] = $data->id;
				break;
			}
		}

		return $suipian;
	}

	public function getlist($user_id)
	{
		$suipian_data = UserSuipianModel::where('user_id', $user_id)->find();
		if (!$suipian_data) {
			$userSuipian = new UserSuipianModel();
			$userSuipian->save(['user_id' => $user_id]);
			$suipian_data['head'] = 0;
			$suipian_data['body'] = 0;
			$suipian_data['left_hand'] = 0;
			$suipian_data['right_hand'] = 0;
			$suipian_data['left_foot'] = 0;
			$suipian_data['right_foot'] = 0;
			$suipian_data['prize'] = 0;
		} else {
			$suipian_data = $suipian_data->toArray();
		}

		return $suipian_data;
	}



	public function tili($data)
	{
		$user_id = $data['user_id'];

		$time = time();
		$tili_time_jiange = ConfigService::get('tili_time_jiange');   // 获得体力的时间间隔
		$tili_limit = ConfigService::get('tili_limit');   // 体力上限

		$suipian_data = UserSuipianModel::where('user_id', $user_id)->find();
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
			if (!$suipian_data) {
				$user_suipian = new UserSuipianModel();
				$user_suipian->save(['tili_time' => $time, 'user_id' => $user_id]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			}

			if ($suipian_data->tili_time == 0) {
				$suipian_data->save(['tili_time' => $time]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			}

			if (($time - $suipian_data->tili_time) < $tili_time_jiange * 60) {
				return [
					'status' => 1,
					'time' => $tili_time_jiange * 60 - ($time - $suipian_data->tili_time),
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			}

			$userRecord->chance_num += 1;
			$userRecord->save();

			if ($userRecord->chance_num < $tili_limit) {
				$suipian_data->save(['tili_time' => $time]);
				return [
					'status' => 1,
					'time' => 0,
					'chance_num' => $userRecord->chance_num,
					'countdown' => 1,
					'tili_limit' => $tili_limit
				];
			} else {
				$suipian_data->save(['tili_time' => 0]);
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


	public function hecheng($data)
	{
		$user_id = $data['user_id'];

		$suipianData = UserSuipianModel::where('user_id', $user_id)->find();
		$userRecord = UserRecordModel::where('user_id', $user_id)->find();

		if (!$suipianData) {
			return [
				'status' => 0,
				'msg' => '系统错误',
			];
		}

		$suipian_data = $suipianData->toArray();
		unset($suipian_data['prize']);
		unset($suipian_data['tili_time']);

		$min = min(array_values($suipian_data));
		if ($min <= 0) {
			return [
				'status' => 0,
				'msg' => '系统错误',
			];
		}

		$suipianData->head -= $min;
		$suipianData->body -= $min;
		$suipianData->left_hand -= $min;
		$suipianData->right_hand -= $min;
		$suipianData->left_foot -= $min;
		$suipianData->right_foot -= $min;
		$suipianData->prize += $min;
		$suipianData->save();

		return [
			'status' => 1,
			'suipian_list' => $suipianData->toArray(),
			'min' => $min,
		];
	}

}