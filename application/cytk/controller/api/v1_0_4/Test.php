<?php

namespace app\cytk\controller\api\v1_0_4;

use think\facade\Request;

use api_data_service\Word as WordService;
use controller\BasicController;

/**
 * 用户控制器类
 */
class Test //extends BasicController
{
	public function index()
	{
		echo "<pre>"; print_r(Request::header());exit();
		require_params('user_id');
        $userId = Request::param('user_id');

        $wordService = new WordService();
        $result = $wordService->getNewWord($userId);

        return result(200, 'ok', $result);
	}
}