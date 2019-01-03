<?php

namespace app\xtxpd\controller\api\v1_0_1;

use think\facade\Request;
use app\xtxpd\service\User as UserService;
use controller\BasicController;

/**
 * 用户控制器类
 */
class User extends BasicController
{

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：https://xtxpd.wqop2018.com/xtxpd/api/v1_0_1/user/login?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
		require_params('code');
		$code = Request::param('code');

		$userService = new UserService();
		$result = $userService->login($code);

		return result(200, 'ok', $result);
	}
}
