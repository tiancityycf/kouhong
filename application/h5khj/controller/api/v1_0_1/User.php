<?php

namespace app\h5khj\controller\api\v1_0_1;

use app\h5khj\service\v1_0_1\User as UserService;
use controller\BasicController;
use think\facade\Request;

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
        $data = Request::param();
        
        $userService = new UserService();
        $result      = $userService->index($data);

        return result(200, 'ok', $result);
    }

    /**
     * 用户登录
     * @return json
     */
    public function login()
    {
        $data        = Request::param();
        $userService = new UserService();
        $result      = $userService->login($data);

        return result(200, 'ok', $result);
    }

    /**
     * 更新用户
     * @return void
     */
    // public function update()
    // {
    //        //前台测试链接：https://h5khj.wqop2018.com/h5khj/api/v1_0_1/user/update.html?openid=1&nickname=xxx&avatar=1&gender=1
    //        require_params('openid', 'nickname', 'avatar', 'gender');
    //     $data = Request::param();

    //     $userService = new UserService();
    //     $result = $userService->update($data);

    //     return result(200, 'ok', $result);
    // }

    /**
     * 提现
     * @return boolean
     */
    public function withdraw()
    {
        require_params('user_id', 'amount', 'type');
        $data = Request::param();

        $userService = new UserService();
        $result      = $userService->withdraw($data);

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

        $userService  = new UserService();
        $withdrawList = $userService->getWithdrawList($userId);

        return result(200, 'ok', ['withdraw_list' => $withdrawList]);
    }
}
