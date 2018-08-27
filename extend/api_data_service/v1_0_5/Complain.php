<?php

namespace api_data_service\v1_0_5;

use model\Complain as ComplainModel;
use model\UserRecord as UserRecordModel;
use model\Heimingdan as HeimingdanModel;
use api_data_service\Config as ConfigService;

/**
 * 投诉建议服务类
 */
class Complain
{
	/**
	 * 创建投诉建议
	 * @param  $data 请求数据
	 * @return boolean
	 */
	public function create($data)
	{
		ComplainModel::create([
			'user_id' => $data['user_id'],
			'type' => $data['type'],
			'create_time' => time(),
		]);
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

		$user_status = $userRecord->user_status;

		$heimingdan_config = ConfigService::get('heimingdan_in_off');
        $zongheimingdan_config = config('heimingdan_zongkaiguan');

        if ($zongheimingdan_config == 1 && $heimingdan_config == 1) {
        	if ($data['type'] == '用户截屏') {
				
				$userRecord->user_status = 0;
				$userRecord->save();

				if ($userRecord->nickname) {
					$heimingdan = HeimingdanModel::where('nickname', $userRecord->nickname)->find();
					if ($heimingdan) {
						$heimingdan->status = 0;
					} else {
						$heimingdan = new HeimingdanModel();
						$heimingdan->nickname = $userRecord->nickname;
					}

					$heimingdan->save();
				}

				$user_status = $userRecord->user_status;
			}
        } else {
        	$user_status = $user_status == 0 ? 1 : $user_status;
        }

        if ($user_status == 2) {
            $user_status = 0;
        }
        
		return ['user_status' => $user_status];

	}


	/*public static function sign($data)
	{
		ksort($data);

		$primary = '';
		foreach ($data as $key => $value) {
			$primary .= $key . ':' . $value;
		}

		$secret = '';
		$sign = md5($primary . $secret);


		return $sign;
	}*/

	public function check($data)
	{
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

		$user_status = $userRecord->user_status;
		
		if ($user_status == 2) {
            $user_status = 0;
        }

		return ['user_status' => $user_status];
	}
}