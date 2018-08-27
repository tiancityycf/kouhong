<?php

namespace api_data_service\v1_0_7;

use think\Db;
use model\ShareRecord as ShareRecordModel;
use model\UserRecord as UserRecordModel;
use model\ShareLog as ShareLogModel;

use api_data_service\Config as ConfigService;
use api_data_service\Share as ShareService;

/**
 * 分享记录
 */
class ShareRecord
{
	/**
     * 分享
     * @param  $userId 用户id
     * @return json
     */
	public function share($data)
	{
		$chance_num = 0;
		$num = rand(4,8);
		$share_date = date('ymd', time());

		if ($share_date != date('ymd', $data['share_time'])) {
			return ['status' => 0, 'chance_num' => 0, 'num' => 0];
		}
		// 开启事务
		Db::startTrans();
		try{
			//$old_model = ShareRecordModel::where('share_user_id',$data['share_user_id'])->where('share_date', $share_date)->lock(true)->find();
			$count = ShareRecordModel::where('share_user_id',$data['share_user_id'])->where('share_date', $share_date)->count();

			$model = new ShareRecordModel();
			$model->share_user_id = $data['share_user_id'];
			$model->share_time = $data['share_time'];
			$model->share_date = date('ymd', $data['share_time']);

			$chance_num = $this->shareGetChance($data, $count);
				
			$model->save();
			Db::commit();
			
			//sleep($num);
			return ['status' => 1, 'chance_num' => $chance_num, 'num' => $num];
		} catch (\Exception $e) {
			Db::rollback();
			trace($e->getMessage(),'error');
			throw new \Exception("系统繁忙");
		} 
	}


	public function click($data)
	{
		$share_click_time = ConfigService::get('share_click_time'); //分享时效
		$share_date = date('ymd',  $data['share_time']);
		$click_date = date('ymd', $data['click_time']);

		if ($click_date != date('ymd', time())) {
			return ['status' => 0];
		}
		// 开启事务
		Db::startTrans();
		try{
			$model = ShareRecordModel::where('share_user_id',$data['share_user_id'])->where('share_time', $data['share_time'])->find();
			$old_model = ShareRecordModel::where('share_user_id',$data['share_user_id'])->where('click_user_id', $data['click_user_id'])->where('share_date', $share_date)->find();
			$userRecord = UserRecordModel::where('user_id', $data['share_user_id'])->find();
			$count = ShareRecordModel::where('share_user_id',$data['share_user_id'])->where('share_date', $share_date)->where('status', 1)->count();

			if ($model->click_status == 0 && !$old_model && $data['share_user_id'] != $data['click_user_id']) {  //判断是否已经被点击或者是不是同一个人点击
				$model->click_status = 1;  // 已经点击
				$model->click_user_id = $data['click_user_id'];
				$model->click_time = $data['click_time'];

				
				if ($model->share_date == $click_date) {  //判断是否过期，是否是当天
					$errorCode = ShareService::decryptedData($data['click_user_id'], $data['encryptedData'], $data['iv'], $result);
					if ($errorCode == 0) {
						$resultArr = json_decode($result, true);
						$group_id = $resultArr['openGId'];
			
						$old_share_log = ShareLogModel::where('user_id', $data['share_user_id'])->where('gid', $group_id)->whereTime('create_time', '>=', 'today')->find();

						if (!$old_share_log) {
							$share_log = new ShareLogModel();
							$share_log->user_id = $data['share_user_id'];
							$share_log->gid = $group_id;
							$share_log->create_time = $data['share_time'];

							$share_log->save();

							$model->status = 1; //分享成功

							if ($count == 0) {
								$userRecord->virtual_num += 4;
							} else {
								$userRecord->virtual_num += 1;
							}
						}
					}
					
				}

				$model->save();
				$userRecord->save();
				Db::commit();
				return ['status' => 1];
			} else {
				return ['status' => 0];
			}

			
			
		} catch (\Exception $e) {
			Db::rollback();
			trace($e->getMessage(),'error');
			throw new \Exception("系统繁忙");
		} 
		
	}

	private function shareGetChance($data, $count)
	{
		$chance_num = 0;
		$userRecord = UserRecordModel::where('user_id', $data['share_user_id'])->find();
		$share_get_chance_num_limit = ConfigService::get('share_get_chance_num_limit'); //每天分享成功获得挑战次数限制
		$num = ConfigService::get('share_get_chance_num'); //分享成功一次获得的挑战次数

		if ($count == 0) {
			$userRecord->share_num = 1;
			$userRecord->chance_num += 1;
			$userRecord->virtual_num = 0;
			$userRecord->last_share = time();
			$userRecord->save();

			$chance_num = 1;
		} else if ($count == 1) {
			if ($userRecord->share_num < $share_get_chance_num_limit) {
				$userRecord->share_num += 1;
				$userRecord->chance_num += 1;
				$userRecord->last_share = time();
				$userRecord->save();

				$chance_num = 1;
			}
		} else if ($count >= 2) {
			if ($userRecord->virtual_num > 0 && ($userRecord->share_num < $share_get_chance_num_limit)) {
				$old_share_log = ShareRecordModel::where('share_user_id',$data['share_user_id'])
					->where('share_date', date('ymd', $data['share_time']))
					->where('status', 1)
					->where('success_num', 0)
					->order('id asc')
					->find();
				if ($old_share_log) {
					$old_share_log->success_num = 1;
					$old_share_log->save();
				}

				$userRecord->share_num += 1;
				$userRecord->chance_num += 1;
				$userRecord->virtual_num -= 1;
				$userRecord->last_share = time();
				$userRecord->save();

				$chance_num = 1;
			}

			
			
		}

		return $chance_num;
	}

	public function info($data)
	{
		$user_id = $data['share_user_id'];
		$share_date = date('ymd', time());
		//$share_click_time_limit = ConfigService::get('share_click_time'); //分享时效

		$allData = ShareRecordModel::where('share_user_id', $user_id)
			->where('share_date', $share_date)
			->order('status desc')
			->select();

		$time_arr = ShareRecordModel::where('share_user_id', $user_id)   //分享时间数组
			->where('share_date', $share_date)
			->order('id asc')
			->column('share_time');

		//echo "<pre>"; echo empty($allData->toArray()) ? '12' : '00';exit();

		$click_time_arr = ShareRecordModel::where('share_user_id', $user_id)  //分享成功点击时间数组
			->where('share_date', $share_date)
			->where('status', 1)
			->order('id asc')
			->column('click_time');

		$count = ShareRecordModel::where('share_user_id', $user_id)  //统计分享成功
			->where('share_date', $share_date)
			->where('status', 1)
			->count();

		$record_list = [];
		$time = time();
		$allData_total = count($allData->toArray());
		$total = 0;

		if ($count == 0 && $allData_total <= 2) {
			$total = $allData_total;
		} else if ($count == 0 && $allData_total > 2) {
			$total = 2;
		} else if ($count >= 1) {
			$num = $allData_total > 5 ? 5 : $allData_total;
			$total = $count + $num;
		}
		
		if (!empty($allData->toArray())) {
			if ($count == 0) {
				$allData_total = $allData_total > 2 ? 2 : $allData_total;
				for ($i=0; $i < $allData_total; $i++) { 
					$record_list[$i]['share_time'] = 0;
					$record_list[$i]['click_time'] = $time_arr[$i]+rand(4,8);
					$record_list[$i]['nickname'] = '';
					$record_list[$i]['avatar'] = '';
					$record_list[$i]['share_status'] = 1;
				}
				
			} else {
				$allData_total = $allData_total > 5 ? 5 : $allData_total;
				for ($i=0; $i < $allData_total; $i++) { 
					if ($i < 2) {
						$record_list[$i]['share_time'] = 0;
						$record_list[$i]['click_time'] = $time_arr[$i]+rand(4,8);
					} else {
						$record_list[$i]['share_time'] = 0;
						$record_list[$i]['click_time'] = $click_time_arr[0]+rand(4,8);
					}
					$record_list[$i]['nickname'] = '';
					$record_list[$i]['avatar'] = '';
					$record_list[$i]['share_status'] = 1;
				}
			}
			
			foreach ($allData as $record) {
				if ($record->status == 1) {
					$record_list[$i]['share_time'] = $record->share_time;
					$record_list[$i]['click_time'] = $record->click_time;
					if ($record->click_user_id == 0) {
						$record_list[$i]['nickname'] = '';
						$record_list[$i]['avatar'] = '';
					} else {
						$record_list[$i]['nickname'] = $record->userRecord->nickname;
						$record_list[$i]['avatar'] = $record->userRecord->avatar;
					}
					$record_list[$i]['share_status'] = $record->status;
				} else {
					$record_list[$i]['share_time'] = $record->share_time;
					$record_list[$i]['click_time'] = 0;
					$record_list[$i]['nickname'] = '';
					$record_list[$i]['avatar'] = '';
					$record_list[$i]['share_status'] = $record->status;
				}

				$i++;
			}
		}

		$configService = new ConfigService();
        $config_data = $configService->getAll();

		return [
			'task_center_title' => $this->getConfigValue($config_data, 'task_center_title'),
			'task_center_txt' => $this->getConfigValue($config_data, 'task_center_txt'),
			'task_center_last_txt' => $this->getConfigValue($config_data, 'task_center_last_txt'),
			'task_center_last_button_txt' => $this->getConfigValue($config_data, 'task_center_last_button_txt'),
			'task_center_total_txt' => $this->getConfigValue($config_data, 'task_center_total_txt'),
			'count' => $total,
			'record_list' => $record_list,
			//'share_click_time_limit' => $share_click_time_limit * 60,
		];
	}

	private function  getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key]: '';
    }
}