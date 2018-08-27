<?php

namespace api_data_service\v1_0_7;

use model\Complain as ComplainModel;
use model\UserRecord as UserRecordModel;
use api_data_service\Config as ConfigService;
use zhise\HttpClient;
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

		$heimingdan_config = ConfigService::get('heimingdan_in_off');
        $zongheimingdan_config = config('heimingdan_zongkaiguan');
        
		if ($zongheimingdan_config && $heimingdan_config) {
            $userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

			$user_status = 0;
			if ($userRecord->nickname && $userRecord->user_status == 1) {
				$arr['nickname'] = $userRecord->nickname;
				$arr['user_status'] = 0;
				$arr['timestamp'] = time();

				$sign = self::sign($arr);
				$arr['sign'] = $sign;
				$url = config('heimingdao_url');

				$result = HttpClient::post($url, $arr);

				
			}
			$userRecord->user_status = 0;
			$userRecord->save();
        } else {
            $user_status = 1;
        }

		

		return ['user_status' => $user_status];

	}


	public static function sign($data)
	{
		ksort($data);

		$primary = '';
		foreach ($data as $key => $value) {
			$primary .= $key . ':' . $value;
		}

		$secret = '';
		$sign = md5($primary . $secret);


		return $sign;
	}

	public function check($data)
	{
		$heimingdan_config = ConfigService::get('heimingdan_in_off');
        $zongheimingdan_config = config('heimingdan_zongkaiguan');
        
		if ($zongheimingdan_config && $heimingdan_config) {
			$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

			$user_status = $userRecord->user_status;
			if ($userRecord && $userRecord->nickname && $userRecord->user_status == 1) {
				$arr['nickname'] = $userRecord->nickname;
				$arr['user_status'] = 1;
				$arr['timestamp'] = time();

				$sign = self::sign($arr);
				$arr['sign'] = $sign;
				$url = config('heimingdao_url');

				$result = HttpClient::post($url, $arr);

				if ($result['status'] === 200 && $result['data']['data']['user_status'] == 0) {
					$userRecord->user_status = 0;
					$userRecord->save();

					$user_status = $userRecord->user_status;
				}
			}
		} else {
			$user_status = 1;
		}
		

		return ['user_status' => $user_status];
	}
}