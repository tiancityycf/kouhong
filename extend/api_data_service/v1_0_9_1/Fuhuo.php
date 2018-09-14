<?php

namespace api_data_service\v1_0_9_1;

use think\facade\Config;
use api_data_service\Config as ConfigService;
use api_data_service\Share as ShareService;
use model\UserRecord as UserRecordModel;
use model\FuhuoShareLog as FuhuoShareLogModel;

class Fuhuo
{
	public function share($data)
	{
		$time = time();
		$date = date('ymd', time());

		$errorCode = ShareService::decryptedData($data['user_id'], $data['encryptedData'], $data['iv'], $result);

		$status = 0;   //接口状态 0 失败  1 成功
		$is_new_group = 0; // 是否分享到重复群  0 重复群  1 未重复
		if ($errorCode == 0) {
			$resultArr = json_decode($result, true);
			$group_id = $resultArr['openGId'];

			$old_share_log = FuhuoShareLogModel::where('user_id', $data['user_id'])
				->where('gid', $group_id)
				->where('create_date', $date)
				->find();

			if (!$old_share_log) {
				$share_log = new FuhuoShareLogModel();
				$share_log->user_id = $data['user_id'];
				$share_log->gid = $group_id;
				$share_log->create_time = $time;
				$share_log->create_date = $date;

				$share_log->save();

				$is_new_group = 1;
			}

			$status = 1;
		}

		return ['status' => $status, 'is_new_group' => $is_new_group];
	}
}