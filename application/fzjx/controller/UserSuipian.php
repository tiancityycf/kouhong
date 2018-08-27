<?php

namespace app\fzjx\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use model\UserSuipian as UserSuipianModel;

class UserSuipian extends BasicAdmin
{
	public function index()
    {
    	$this->title = '玩家娃娃碎查看';

       	list($get, $db) = [$this->request->get(), new UserSuipianModel()];

        $db = $db->search($get);

        $this->assign('get', $get);
       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@user_suipian/index', $result);
    }
}