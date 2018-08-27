<?php

namespace app\tzdzz\controller\api\v1_0_5;

use think\facade\Request;
use api_data_service\Share as ShareService;

/**
 * 用户分享控制器类
 */
class Share
{
	/**
	 * 分享
	 * @return json
	 */
	public function share()
	{
		require_params('user_id', 'encryptedData', 'iv', 'share_type');
		$data = Request::param();

		$shareService = new ShareService();
		$result = $shareService->share($data);

		return result(200, '0k', $result);
	}
}