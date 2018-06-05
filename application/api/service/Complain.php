<?php

namespace app\api\service;

use app\api\model\Complain as ComplainModel;

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
	}
}