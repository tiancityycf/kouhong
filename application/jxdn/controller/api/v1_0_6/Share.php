<?php

namespace app\jxdn\controller\api\v1_0_6;

use think\facade\Request;
use api_data_service\Share as ShareService;
use api_data_service\v2_0_2\NiuNiu as NiuNiuService;

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

		$shareService = new NiuNiuService();
		$result = $shareService->share($data);

		return result(200, '0k', $result);
	}
}