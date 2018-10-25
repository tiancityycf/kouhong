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
	protected $configData;

    public function __construct($configData)
    {
        $this->configData = $configData;
    }

	public function index($data)
	{
		echo "<pre>"; print_r($this->configData);exit();
	}
}