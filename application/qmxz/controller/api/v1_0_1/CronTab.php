<?php

namespace app\qmxz\controller\api\v1_0_1;

use app\qmxz\service\v1_0_1\CronTab as CronTabService;
use controller\BasicController;

/**
 * 脚本制器类
 */
class CronTab extends BasicController
{
	public function sendNotice(){
		$cron_tab = new CronTabService($this->configData);
		//从数据库中发送模板消息
		// $result = $cron_tab->sendNotice();
		//从缓存中发送模板消息
		$result = $cron_tab->redisSendNotice();

		return result(200, 'ok', $result);
	}
}