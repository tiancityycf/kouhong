<?php

namespace app\fzjx\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use model\Suipian as SuipianModel;

class Suipian extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'suipian';

    public function index()
    {
    	$this->title = '娃娃碎片几率设置';

       	list($get, $db) = [$this->request->get(), new SuipianModel()];

        $db = $db->search($get);

        $this->assign('get', $get);
       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@suipian/index', $result);
    }


    public function add()
    {
    	return $this->_form($this->table, 'admin@suipian/form');
    }

    public function edit()
    {
    	return $this->_form($this->table, 'admin@suipian/form');
    }
}