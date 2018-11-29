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
        trace('login-uid='.session('uid'),'error');
        if (!session('uid')) {
            trace('用户uid不存在', 'error');
            $this->redirect("login/index");
        } else {
            $user_id     = session('uid');
            $last_login  = session('last_login');
            $openid      = session('openid');
            $user_status = session('user_status');
            $money       = session('money');
            trace('user_id='.$user_id,'error');
            trace('last_login='.$last_login,'error');
            trace('openid='.$openid,'error');
            trace('user_status='.$user_status,'error');
            trace('money='.$money,'error');
            $this->assign('user_id', $user_id);
            $this->assign('last_login', $last_login);
            $this->assign('openid', $openid);
            $this->assign('user_status', $user_status);
            $this->assign('money', $money);
        }
        
        // $user_id     = 3;
        // $last_login  = date('Y-m-d H:i:s',1543393349);
        // $openid      = 'olHrk1LSJxL1GBcrtnhIq8snHKGE';
        // $user_status = 1;
        // $money       = 0.00;
        // $this->assign('user_id', $user_id);
        // $this->assign('last_login', $last_login);
        // $this->assign('openid', $openid);
        // $this->assign('user_status', $user_status);
        // $this->assign('money', $money);
    }
}
