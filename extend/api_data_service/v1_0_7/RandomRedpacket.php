<?php

namespace api_data_service\v1_0_7;

use think\Db;

use api_data_service\Log as LogService;
use api_data_service\Config as ConfigService;
use api_data_service\Share as ShareService;

use model\UserRecord as UserRecordModel;
use model\RedpacketLog as RedpacketLogModel;
use model\RandomRedpacket as RandomRedpacketModel;
use model\ShareLog as ShareLogModel;
use model\ShareRedpacket as ShareRedpacketModel;

/**
 * 随机红包服务类
 */
class RandomRedpacket
{
	public function create($user_id)
	{
		$share_date = date('ymd', time());
		

		$today_redpacket_num = ConfigService::get('today_redpacket_num');
		$today_personal_random_redpacket_num = ConfigService::get('today_personal_random_redpacket_num');
		$random_percentage = ConfigService::get('random_percentage');
		$rand_num = rand(1,100);

		$count = RandomRedpacketModel::where('share_date', $share_date)->count();
		$personal_total = RandomRedpacketModel::where('share_date', $share_date)->where('share_user_id',$user_id)->count();

		$random_redpacket_id = 0;
		if ($count < $today_redpacket_num && $personal_total < $today_personal_random_redpacket_num && $rand_num >= (100-$random_percentage)) {
			list($min, $max) = ConfigService::get('random_redpacket_range');
			$amount = rand($min * 100, $max * 100) / 100;

			$model = new RandomRedpacketModel();

			$model->share_user_id = $user_id;
			$model->share_time = time(); //记录创建时间
			$model->share_date = $share_date;
			$model->amount = $amount;

			$model->save();

			$random_redpacket_id = $model->id;
		}


		return $random_redpacket_id;
	}


	public function click($data)
	{
		//$share_date = date('ymd', $data['share_time']);
		// 开启事务
		Db::startTrans();
		try{
			$model = RandomRedpacketModel::where('share_user_id',$data['share_user_id'])->where('id', $data['random_redpacket_id'])->find();
			if ($model) {
				$old_model = RandomRedpacketModel::where('share_user_id',$data['share_user_id'])->where('click_user_id', $data['click_user_id'])->where('share_date', $model->share_date)->find();
				$userRecord = UserRecordModel::where('user_id', $data['share_user_id'])->find();

				$click_date = date('ymd', time());
				if ($model->click_status == 0 && !$old_model && $data['share_user_id'] != $data['click_user_id'] && $model->share_date == $click_date) {  //判断是否已经被点击或者是不是同一个人点击  //判断是否过期，是否是当天

					$errorCode = ShareService::decryptedData($data['click_user_id'], $data['encryptedData'], $data['iv'], $result);
					if ($errorCode == 0) {
						$resultArr = json_decode($result, true);
						$group_id = $resultArr['openGId'];
			
						$old_share_log = ShareRedpacketModel::where('user_id', $data['share_user_id'])->where('gid', $group_id)->where('create_date', $model->share_date)->find();

						if (!$old_share_log) {
							$share_log = new ShareRedpacketModel();
							$share_log->user_id = $data['share_user_id'];
							$share_log->gid = $group_id;
							$share_log->create_time = $model->share_time;
							$share_log->create_date = $model->share_date;

							$share_log->save();
							$model->click_status = 1;  // 已经点击
							$model->click_user_id = $data['click_user_id'];
							$model->click_time = time();

							$model->status = 1; //分享成功

							$userRecord->amount += $model->amount;
							$userRecord->amount_total += $model->amount;

						}
					}

					$model->save();
					$userRecord->save();

					Db::commit();
					return ['status' => 1];
				}
			}
			
			return ['status' => 0];
		} catch (\Exception $e) {
			Db::rollback();
			trace($e->getMessage(),'error');
			throw new \Exception("系统繁忙");
		} 
	}


	public function getList($user_id)
	{
		$list = [];
		$share_date = date('ymd', time());

		$random_list = RandomRedpacketModel::where('share_user_id', $user_id)->where('share_date', $share_date)->order('status asc')->select();

		$i = 0;
		foreach ($random_list as $random) {
			$list[$i]['amount'] = $random->amount;
			$list[$i]['create_time'] = date('Y-m-d H:i:s', $random->click_time);
			$list[$i]['status'] = $random->status;
			if ($random->status == 1) {
				$list[$i]['random_redpacket'] = 0;
			} else {
				$list[$i]['random_redpacket'] = 1;
			}
			
			$list[$i]['random_redpacket_id'] = $random->id;
			$i++;
		}

		$receiveList = RedpacketLogModel::where('user_id', $user_id)->order('id desc')->select();
        foreach ($receiveList as $receive) {
            $list[$i] = [
                'amount' => $receive->amount,
                'create_time' => $receive->create_time,
                'status' => 0,
                'random_redpacket' => 0,
                'random_redpacket_id' => 0,
            ];
            $i++;
        }

        return $list;

	}


	public function check($data)
	{
		$model = RandomRedpacketModel::where('share_user_id',$data['share_user_id'])->where('id', $data['random_redpacket_id'])->find();

		if ($model && $model->status == 1) {
			$status = 1;
		} else {
			$status = 0;
		}

		return ['status' => $status];
	}

	private function  getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key]: '';
    }


	public function txt()
	{
		$configService = new ConfigService();
        $config_data = $configService->getAll();

		return [
			'random_redpacket_top_txt1' => $this->getConfigValue($config_data, 'random_redpacket_top_txt1'),
			'random_redpacket_top_txt2' => $this->getConfigValue($config_data, 'random_redpacket_top_txt2'),
			'random_redpacket_button_txt' => $this->getConfigValue($config_data, 'random_redpacket_button_txt'),
			'random_redpacket_wait_txt' => $this->getConfigValue($config_data, 'random_redpacket_wait_txt'),
			'random_redpacket_wait_button_txt1' => $this->getConfigValue($config_data, 'random_redpacket_wait_button_txt1'),
			'random_redpacket_wait_button_txt2' => $this->getConfigValue($config_data, 'random_redpacket_wait_button_txt2'),
			'random_redpacket_false_txt' => $this->getConfigValue($config_data, 'random_redpacket_false_txt'),
			'random_redpacket_false_button_txt1' => $this->getConfigValue($config_data, 'random_redpacket_false_button_txt1'),
			'random_redpacket_false_button_txt2' => $this->getConfigValue($config_data, 'random_redpacket_false_button_txt2'),
			'random_redpacket_success_txt' => $this->getConfigValue($config_data, 'random_redpacket_success_txt'),
			'random_redpacket_success_button_txt' => $this->getConfigValue($config_data, 'random_redpacket_success_button_txt'),
		];
	}


	public function amount($data)
	{
		$model = RandomRedpacketModel::where('share_user_id',$data['share_user_id'])->where('id', $data['random_redpacket_id'])->find();

		if ($model && $model->status == 1) {
			$status = 1;
			$amount = $model->amount;
		} else {
			$status = 0;
			$amount = 0;
		}

		return ['status' => $status, 'amount' => $amount];
	}
}