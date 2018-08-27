<?php

namespace app\ssszw\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\v1_0_7\ShareRecord as ShareRecordService;
use controller\BasicController;

/**
 * 用户分享控制器类
 */
class ShareRecord extends BasicController
{
	/**
	 * 分享
	 * @return json
	 */
	public function share()
	{
		require_params('share_user_id', 'share_time');
		$data = Request::param();

		$shareService = new ShareRecordService();
		$result = $shareService->share($data);

		return result(200, '0k', $result);
	}


	/**
	 * 点击
	 * @return json
	 */
	public function click()
	{
		require_params('share_user_id', 'share_time', 'click_user_id', 'click_time');
		$data = Request::param();

		$shareService = new ShareRecordService();
		$result = $shareService->click($data);

		return result(200, '0k', $result);
	}

	public function check()
	{
		require_params('share_user_id');
		$data = Request::param();

		$shareService = new ShareRecordService();
		$result = $shareService->check($data);

		return result(200, '0k', $result);
	}


	public function info()
	{
		require_params('share_user_id');
		$data = Request::param();

		$shareService = new ShareRecordService();
		$result = $shareService->info($data);

		return result(200, '0k', $result);
	}
}