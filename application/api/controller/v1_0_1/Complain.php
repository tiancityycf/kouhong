<?php

namespace app\api\controller\v1_0_1;

use think\facade\Request;
use app\api\service\Complain as ComplainService;

use app\api\controller\v1_0_1\BasicController;

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
		$complainService->create($data);

		return result();
	}
}