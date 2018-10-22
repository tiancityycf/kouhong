<?php

namespace app\qmxz\controller;

use controller\BasicAdmin;
use think\Db;
use app\qmxz\model\Special as SpecialModel;

//整点场控制器类
class Special extends BasicAdmin
{
	public function index()
    {
    	$this->title = '整点场管理';

       	list($get, $db) = [$this->request->get(), new SpecialModel()];

        $db = $db->search($get);

       	return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
        	$model = new SpecialModel();
        	$data['display_time'] = strtotime($data['display_time']);
        	$data['create_time'] = time();
        	
        	if ($model->save($data) != false) {
        		$this->success('恭喜, 数据保存成功!', '');
        	} else {
        		$this->error('数据保存失败, 请稍候再试!');
        	}
        }

        return  $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = SpecialModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
        	$post_data['display_time'] = strtotime($post_data['display_time']);

        	if ($vo->save($post_data) !== false) {
        	    $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $vo->display_time = date('Y-m-d H:i:s', $vo->display_time);
        return  $this->fetch('form', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
    	$data = $this->request->post();
    	if ($data) {
            $model = SpecialModel::get($data['id']);
    		if($model->delete() !== false){
    			$this->success("删除成功！", '');
    		}
    	}

    	$this->error("删除失败，请稍候再试！");
    }
}