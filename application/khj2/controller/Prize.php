<?php

namespace app\khj2\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

class Prize extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'special_prize';

    public function index()
    {
        $this->title = '奖品列表';

        list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['name'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        $db->order('id desc');

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('index', $result);
    }

    public function add()
    {
        return $this->_form($this->table, 'form');
    }

    public function edit()
    {
        return $this->_form($this->table, 'form');
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
