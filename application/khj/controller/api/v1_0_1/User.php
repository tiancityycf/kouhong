<?php

namespace app\khj\controller\api\v1_0_1;

use think\facade\Request;
use think\Db;
use app\khj\service\v1_0_1\User as UserService;
use app\khj\model\User as UserModel;
use controller\BasicController;
use app\khj\service\v1_0_1\Index as IndexService;

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
        //前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/user/index.html?user_id=1
        require_params('user_id');
        $data = Request::param();

		$user_info = Db::name('user_record')->field('avatar,nickname,gold,money')->where('user_id',$data['user_id'])->find();
        $user_info['count'] = Db::name('success_log')->where('user_id',$data['user_id'])->count();
		$result['user_info'] =  $user_info;
        return result(200, 'ok', $result);
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/user/login.html?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
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
        //前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/user/update.html?openid=1&nickname=xxx&avatar=1&gender=1
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
}
