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
            $user_id     = 3;
            session('uid',3);
            $last_login  = date('Y-m-d H:i:s', 1543393349);
            $openid      = 'olHrk1LSJxL1GBcrtnhIq8snHKGE';
            $user_status = 1;
            $money       = 0.00;
            $this->assign('user_id', $user_id);
            $this->assign('openid', $openid);
            $this->assign('user_status', $user_status);


            // if (session('uid')) {
            //     $user_id     = session('uid');
            //     $openid      = session('openid');
            //     $user_status = session('user_status');
            //     $this->assign('user_id', $user_id);
            //     $this->assign('openid', $openid);
            //     $this->assign('user_status', $user_status);
            // } else {
            //     $this->redirect("login/index");
            // }
    }
}
