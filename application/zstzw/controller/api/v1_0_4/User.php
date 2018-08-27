<?php

namespace app\zstzw\controller\api\v1_0_4;

use think\facade\Request;

use api_data_service\v1_0_7\User as UserService;
use controller\BasicController;

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
		$from_type = 1;

		$userService = new UserService();
		$result = $userService->login($code, $from_type);

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
		$result = $userService->update($data);

		return result(200, 'ok', $result);
	}

	/**
	 * 提现
	 * @return boolean
	 */
	public function withdraw()
	{
		require_params('user_id', 'amount');
		$data = Request::param();

		$userService = new UserService();
		$result = $userService->withdraw($data);

		return result(200, 'ok', $result);
	}

	/**
	 * 提现记录
	 * @return array
	 */
	public function withdrawList()
	{
		require_params('user_id');
		$userId = Request::param('user_id');

		$userService = new UserService();
		$withdrawList = $userService->getWithdrawList($userId);

		return result(200, 'ok', ['withdraw_list' => $withdrawList]);
	}
}