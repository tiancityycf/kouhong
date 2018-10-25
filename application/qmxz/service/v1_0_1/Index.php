<?php

namespace app\qmxz\service\v1_0_1;

use think\Db;
use app\qmxz\model\User as UserModel;
use app\qmxz\model\UserRecord as UserRecordModel;
use app\qmxz\service\Config as ConfigService;

/**
 * 首页服务类
 */
class Index
{

	public function index($data, $configData)
	{
		$user_id = $data['user_id'];

		print_r($configData);
	}
}