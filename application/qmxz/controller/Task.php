<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\qmxz\controller;

use app\qmxz\model\Task as TaskModel;
use controller\BasicAdmin;
use think\Db;

/**
 * Description of Task
 *
 * @author 157900869@qq.com
 */
class Task extends BasicAdmin {

    public function index() {
        $this->title = '任务列表';
        list($get, $db) = [$this->request->get(), Db::name("task")];
     
        $db->order('id', 'asc');
        $this->assign('get', $get);
        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('Task/index', $result);
    }
    
    public function add(){
        $data = $this->request->post();

        if ($data) {
            //echo "<pre>"; print_r($data);exit();
            $arr = [];
            $arr = $data;
            $arr['create_time'] = time();

            if (Db::name("task")->strict(false)->insert($arr) !== false) 
            {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }

        }
        return  $this->fetch('add');
    }
  /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = Db::name("task")->where('id',$get_data['id'])->find();
        $post_data = $this->request->post();
        if ($post_data) {
      
            $arr = [];
            $arr = $post_data;

            if (Db::name("task")->where(['id' => $get_data['id']])->update($arr) !== false) {              
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('edit', ['vo' => $vo]);
    }
       /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function delete()
    {
        $get_data = $this->request->get();
        $res = Db::name("task")->where('id',$get_data['id'])->delete();

        if ($res) {
            $this->success('成功删除数据!', '');
        } else {
            $this->error('删除数据失败!');
        }

    }
    public function logger() {
        
    }

}
