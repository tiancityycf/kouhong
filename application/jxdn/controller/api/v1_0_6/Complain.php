<?php

namespace app\jxdn\controller\api\v1_0_6;

use think\facade\Request;
use api_data_service\v2_0_1\Complain as ComplainService;
use controller\BasicController;

/**
 * 投诉建议类
 */
class Complain extends BasicController
{
	/**
	 * 创建投诉建议
	 * @return json
	 */
	public function create()
	{
		require_params('user_id', 'type');
		$data = Request::param();

		$complainService = new ComplainService();
		$result = $complainService->create($data);

		return result(200, 'ok', $result);
	}
}