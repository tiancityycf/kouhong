<?php

namespace app\nickname\controller;

use controller\BasicAdmin;
use service\DataService;
use model\Whitelist as WhitelistModel;

class NickName extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'whitelist';

    public function index()
    {
    	$this->title = '小程序可访问ip配置';

       	list($get, $db) = [$this->request->get(), new WhitelistModel()];

        $db = $db->search($get);
       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@nickname/index', $result);
    }

    //添加
    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $model = new WhitelistModel();
            if ($model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        
        return  $this->fetch('admin@nickname/form', ['vo' => $data]);
    }

    //编辑
    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = WhitelistModel::get($get_data['id']);

        $post_data = $this->request->post();
        if ($post_data) {
            if ($vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('admin@nickname/form', ['vo' => $vo->toArray()]);
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