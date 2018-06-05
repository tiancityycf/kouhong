<?php

namespace app\api\controller\v1;

use think\facade\Request;
use app\api\controller\v1\BasicController;
use app\api\service\Complain as ComplainService;

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