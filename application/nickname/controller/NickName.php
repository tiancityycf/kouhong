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
        $user = session('user');

        $db = $db->search($get, $user['id']);
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
            $model->title = $data['title'];
            $model->appid = $data['appid'];
            $model->ips = $data['ips'];
            $model->memo = $data['memo'];
            $model->app_secret = $data['app_secret'];
            $user = session('user');
            $model->admin_user_id = $user['id'];
            $model->create_time = time();
            if ($model->save() !== false) {
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
            $vo->title = $post_data['title'];
            $vo->appid = $post_data['appid'];
            $vo->ips = $post_data['ips'];
            $vo->memo = $post_data['memo'];
            $vo->app_secret = $post_data['app_secret'];
            $vo->update_time = time();
            if ($vo->save() !== false) {
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