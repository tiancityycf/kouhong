<?php

namespace app\khj2\controller\api\v1_0_1;

use think\facade\Request;
use think\Db;
use app\khj2\service\v1_0_1\User as UserService;
use app\khj2\model\User as UserModel;
use controller\BasicController;
use app\khj2\service\v1_0_1\Index as IndexService;

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
        //前台测试链接：https://khj2.wqop2018.com/khj2/api/v1_0_1/user/index.html?user_id=1
        require_params('user_id');
        $data = Request::param();

		$user_info = Db::name('user_record')->field('avatar,nickname,gold,money')->where('user_id',$data['user_id'])->find();
		$where = [];
        $where['user_id'] = $data['user_id'];
        $where['successed'] = 1;
        $user_info['count'] = Db::name('challenge_log')->where($where)->count();
        $user_info['limit'] = isset($this->configData['success_num'])?$this->configData['success_num']:0;
		$result['user_info'] =  $user_info;
        return result(200, 'ok', $result);
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：https://khj2.wqop2018.com/khj2/api/v1_0_1/user/login.html?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
		require_params('code');
		$code = Request::param('code');
		$invite_id = Request::param('invite_id');
	
		$userService = new UserService();
		$result = $userService->login($code,$invite_id);

		return result(200, 'ok', $result);
	}

	/**
	 * 更新用户
	 * @return void
	 */
	public function update()
	{
        //前台测试链接：https://khj2.wqop2018.com/khj2/api/v1_0_1/user/update.html?openid=1&nickname=xxx&avatar=1&gender=1
        require_params('user_id', 'nickname', 'avatar', 'gender');
		$data = Request::param();

		$userService = new UserService();
		$result = $userService->update($data);

		return result(200, 'ok', $result);
	}

	/**
	 * @return boolean
	 */
	public function friend()
	{
		require_params('user_id');
		$data = Request::param();

		$userService = new UserService();
		$result = $userService->friend($data);

		return result(200, 'ok', $result);
	}
	/**
	 * @return boolean
	 */
	public function video()
	{
		require_params('user_id','ad_id','is_end');
		$data = Request::param();

		$userService = new UserService();
		$result = $userService->video($data);

		return result(200, 'ok', $result);
	}
	/**
	 * @return boolean
	 */
	public function playtimes()
	{
		require_params('user_id');
		$data = Request::param();

		$userService = new UserService();
		$result = $userService->playtimes($data);

		return result(200, 'ok', $result);
	}
	/**
	 * @return boolean
	 */
	public function broadcast()
	{

		$userService = new UserService();
		$result = $userService->broadcast();

		return result(200, 'ok', $result);
	}

}
