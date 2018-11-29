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
        if (Request::param('test') == 1) {
            $user_id     = 3;
            $last_login  = date('Y-m-d H:i:s', 1543393349);
            $openid      = 'olHrk1LSJxL1GBcrtnhIq8snHKGE';
            $user_status = 1;
            $money       = 0.00;
            $this->assign('user_id', $user_id);
            $this->assign('last_login', $last_login);
            $this->assign('openid', $openid);
            $this->assign('user_status', $user_status);
            $this->assign('money', $money);
        } else {
            if (session('uid')) {
                trace('用户uid存在', 'error');
                $user_id     = session('uid');
                $last_login  = session('last_login');
                $openid      = session('openid');
                $user_status = session('user_status');
                $money       = session('money');
                trace('uid2=' . $user_id, 'error');
                trace('last_login1=' . $last_login, 'error');
                trace('openid1=' . $openid, 'error');
                trace('user_status1=' . $user_status, 'error');
                trace('money1=' . $money, 'error');
                $this->assign('user_id', $user_id);
                $this->assign('last_login', $last_login);
                $this->assign('openid', $openid);
                $this->assign('user_status', $user_status);
                $this->assign('money', $money);
            } else {
                trace('用户uid不存在', 'error');
                $this->redirect("login/index");
            }
        }
    }
}
