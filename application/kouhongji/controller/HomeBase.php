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
        if (!session('?uid')) {
        	$this->redirect("login/index");
        }
    }
}