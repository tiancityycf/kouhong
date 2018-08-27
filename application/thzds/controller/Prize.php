<?php

namespace app\thzds\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

class Prize extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'prize';

    public function index()
    {
    	$this->title = '礼物配置';

       	list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['name'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@prize/index', $result);
    }

    public function add()
    {
    	return $this->_form($this->table, 'admin@prize/form');
    }

    public function edit()
    {
    	return $this->_form($this->table, 'admin@prize/form');
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
    	if (DataService::update($this->table)) {
            $this->success("禁用成功！", '');
        }
        $this->error("禁用失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
    	if (DataService::update($this->table)) {
            $this->success("启用成功！", '');
        }
        $this->error("启用失败，请稍候再试！");
    }
}