<?php

namespace app\zstzw\service\v1_0_4;

use model\Complain as ComplainModel;
use model\UserRecord as UserRecordModel;
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

		return ['status' => 1];

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
	}

	public function check($data)
	{
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

		$user_status = $userRecord->user_status;
		if ($userRecord && $userRecord->nickname && $userRecord->user_status == 1) {
			$arr['nickname'] = $userRecord->nickname;
			$arr['user_status'] = 1;
			$arr['timestamp'] = time();

			$sign = self::sign($arr);
			$arr['sign'] = $sign;
			$url = 'http://kf.ali-yun.wang/api/complain_user/check';

			$result = HttpClient::post($url, $arr);

			if ($result['status'] === 200 && $result['data']['data']['user_status'] == 0) {
				$userRecord->user_status = 0;
				$userRecord->save();

				$user_status = $userRecord->user_status;
			}
		}

		return ['user_status' => $user_status];
	}*/
}