<?php
namespace app\kouhongji\controller;

use think\Controller;

class HomeBase extends Controller
{
	/**
     * 控制器基础方法
     */
    public function initialize()
    {
    	tracce('uid='.session('uid'),'error');
        if (!session('uid')) {
        	tracce('用户uid不存在','error');
        	$this->redirect("login/index");
        }
    }
}