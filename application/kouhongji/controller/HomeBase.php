<?php
namespace app\kouhongji\controller;

use think\Controller;
use think\facade\Request;

class HomeBase extends Controller
{
    /**
     * 控制器基础方法
     */
    public function initialize()
    {
        trace('uid=' . session('uid'), 'error');
        if (!session('uid')) {
            trace('用户uid不存在', 'error');
            $this->redirect("login/index");
        } else {
            $user_id     = Request::param('user_id');
            $last_login  = Request::param('last_login');
            $openid      = Request::param('openid');
            $user_status = Request::param('user_status');
            $money       = Request::param('money');
            $this->assign('user_id', $user_id);
            $this->assign('last_login', $last_login);
            $this->assign('openid', $openid);
            $this->assign('user_status', $user_status);
            $this->assign('money', $money);
        }
    }
}
