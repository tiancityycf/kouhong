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

    /**
     * 用户佣金记录
     * @return array
     */
    public function userRelationList()
    {
        require_params('user_id');
        $data = Request::param();

        $userService  = new UserService();
        $result = $userService->userRelationList($data);

        return result(200, 'ok', $result);
    }


    public function saveCode()
    {
        require_params('user_id', 'img_content');
        $data = Request::param();

        $userService  = new UserService();
        $result = $userService->saveCode($data);

        return result(200, 'ok', $result);
    }
}
