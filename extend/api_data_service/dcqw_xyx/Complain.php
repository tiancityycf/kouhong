<?php

namespace api_data_service\dcqw_xyx;

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


		return ['status' => 1];

}