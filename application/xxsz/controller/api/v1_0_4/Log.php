<?php

namespace app\xxsz\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\Log as LogService;

use controller\BasicController;

/**
 * 用户日志控制器类
 */
class Log extends BasicController
{
	/**
	 * 创建日志
	 * @return void
	 */
	public function create()
	{
		require_params('user_id', 'type');
		$data = Request::param();

		$logService = new LogService();
		switch ($data['type']) {
			case 1: // 微信formid日志
				require_params('formid');
				$logService->createFormidLog($data);
				break;
			case 2: // 推广链接日志
				require_params('app_id');
				$logService->createLinkLog($data);
				break;
			case 3: // 广告位日志
				require_params('advertisement_id');
				$logService->createAdvertisementLog($data);
				break;
			default:
				throw new \Exception();
				break;
		}
	}
}