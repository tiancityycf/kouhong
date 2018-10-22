<?php

namespace app\qmxz\controller\api\v1_0_1;

use think\facade\Request;

use app\qmxz\service\v1_0_1\User as UserService;
use app\qmxz\model\User as UserModel;
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
		require_params('openid', 'encryptedData', 'iv');
        $data = Request::param();
        $result = [];
        return result(200, 'ok', $result);
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/user/login.html?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
		require_params('code');
		$code = Request::param('code');
		$from_type = Request::param('from_type') ? Request::param('from_type') : 0;

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
		require_params('openid', 'nickname', 'avatar', 'gender');
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


	/**
	 * 个人用户中心
	 * @return array
	 */

	public function center()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/user/center.html?openid=1&sign=0a53bf188436d7372adfa7e613217f01&timestamp=1
		require_params('openid');
        $openid = Request::param('openid');

        $userInfo = new UserModel();
        $result = $userInfo->field('nickname,avatar')->where('openid',$openid)->find();
        return result(200, 'ok', $result);
	}
}