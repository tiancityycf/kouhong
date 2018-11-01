<?php

namespace app\qmxz\controller;

use controller\BasicAdmin;
use think\Db;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\validate\SpecialWord as SpecialWordValidate;
use app\qmxz\model\Special as SpecialModel;

//整点场下面的题目控制器类
class SpecialWord extends BasicAdmin
{

    //字段验证
    protected function checkData($data)
    {
        $validate = new SpecialWordValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }
    
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
            $data['options'] = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
        	$model = new SpecialWordModel();
        	$data['create_time'] = time();
        	
        	if ($this->checkData($data) === true && $model->save($data) != false) {
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
            $post_data['options'] = json_encode($post_data['options'], JSON_UNESCAPED_UNICODE);
        	if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
        	    $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_list();
        return  $this->fetch('edit', ['vo' => $vo->getdata()]);
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