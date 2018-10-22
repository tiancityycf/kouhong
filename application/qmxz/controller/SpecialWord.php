<?php

namespace app\qmxz\controller;

use controller\BasicAdmin;
use think\Db;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\model\Special as SpecialModel;

//整点场下面的题目控制器类
class SpecialWord extends BasicAdmin
{
	public function index()
    {
    	$this->title = '整点场题目管理';

       	list($get, $db) = [$this->request->get(), new SpecialWordModel()];

        $db = $db->search($get);

        $this->special_list();
       	return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
        	$model = new SpecialWordModel();
        	$data['create_time'] = time();
        	
        	if ($model->save($data) != false) {
        		$this->success('恭喜, 数据保存成功!', '');
        	} else {
        		$this->error('数据保存失败, 请稍候再试!');
        	}
        }
        $this->special_list();
        return  $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = SpecialWordModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
        	if ($vo->save($post_data) !== false) {
        	    $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_list();
        return  $this->fetch('form', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
    	$data = $this->request->post();
    	if ($data) {
            $model = SpecialWordModel::get($data['id']);
    		if($model->delete() !== false){
    			$this->success("删除成功！", '');
    		}
    	}

    	$this->error("删除失败，请稍候再试！");
    }

    protected function special_list()
    {
    	$data = SpecialModel::column('title', 'id');

    	$this->assign('special_list', $data);
    }
}