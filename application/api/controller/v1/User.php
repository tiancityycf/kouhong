<?php

namespace app\api\controller\v1;

use think\facade\Request;
use app\api\service\User as UserService;
use app\api\controller\v1\BasicController;

/**
 * 用户控制器类
 */
class User extends BasicController
{
	/**
	 * 用户首页
	 * @return json
	 */
	public function index()
	{
		require_params('user_id');
        $userId = Request::param('user_id');

        $userService = new UserService();
        $result = $userService->index($userId);

        return result(200, 'ok', $result);
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		require_params('code');
		$code = Request::param('code');

		$userService = new UserService();
		$result = $userService->login($code);

		return result(200, 'ok', $result);
	}

	/**
	 * 更新用户
	 * @return void
	 */
	public function update()
	{
		require_params('user_id', 'nickname', 'avatar', 'gender');
		$data = Request::param();
		
		$userService = new UserService();
		$userService->update($data);

		return result();
	}
}