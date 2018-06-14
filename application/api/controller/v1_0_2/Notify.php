<?php

namespace app\api\controller\v1_0_2;

use think\facade\Request;

use app\api\service\v1_0_2\Notify as NotifyService;

/**
 * 通知类(用于第三方系统回调)
 */
class Notify
{
	/**
	 * 提现通知(公司公众平台调用)
	 * @return string
	 */
	public function withdraw()
	{
		$data = Request::param();
		$notifyService = new NotifyService();
		return $notifyService->withdraw($data);
	}
}