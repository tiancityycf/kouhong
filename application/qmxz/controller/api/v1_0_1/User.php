<?php

namespace app\qmxz\controller\api\v1_0_1;

use think\facade\Request;
use think\Db;
use app\qmxz\service\v1_0_1\User as UserService;
use app\qmxz\model\User as UserModel;
use controller\BasicController;
use app\qmxz\service\v1_0_1\Index as IndexService;

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
		require_params('openid');
        $data = Request::param();

		$user_info = Db::name('user_record')->field('avatar,nickname,gold')->where('openid',$data['openid'])->find();
		$result['user_info'] =  $user_info;

		//是否跳转小程序
		$config_data = $this->configData;
		$is_jump = $config_data['is_jump'];
		$result['is_jump'] = $is_jump;

		$indexService = new IndexService();
		$result['hot_goods'] = $indexService->hot_goods();

        $config_data = $this->configData;
        $result['config'] = $config_data;

        return result(200, 'ok', $result);
	}

	/**
	 * 用户登录
	 * @return json
	 */
	public function login()
	{
		//前台测试链接：http://qmxz.com/qmxz/api/v1_0_1/user/login.html?code=1&sign=d7e197d95a418afdc1914bd0e32a94b2&timestamp=1
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
		//前台测试链接：http://www.zhuqian.com/qmxz/api/v1_0_0/user/center.html?openid=1&sign=0a53bf188436d7372adfa7e613217f01&timestamp=1
		require_params('openid');
        $openid = Request::param('openid');

        $userInfo = new UserModel();
        $result = $userInfo->field('nickname,avatar')->where('openid',$openid)->find();
        return result(200, 'ok', $result);
	}
}
