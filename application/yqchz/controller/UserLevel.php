<?php
namespace app\yqchz\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use model\UserLevel as UserLevelModel;
use model\UserLevelWord as UserLevelWordModel;
use validate\UserLevel as UserLevelValidate;
use validate\UserLevelWord as UserLevelWordValidate;

class UserLevel extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'user_level';

    public function index()
    {
    	$this->title = '用户难度等级设置';

       	list($get, $db) = [$this->request->get(), new UserLevelModel()];

        $db = $db->search($get);

        $this->assign('get', $get);

        $result = parent::_list($db, false, false, false);
        $this->assign('title', $this->title);
       	return  $this->fetch('admin@user_level/index', $result);
    }

    //添加
    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $model = new UserLevelModel();
            if ($this->checkData($data) === true && $model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        
        return  $this->fetch('admin@user_level/form', ['vo' => $data]);
    }

    //编辑
    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = UserLevelModel::get($get_data['id']);

        $post_data = $this->request->post();
        if ($post_data) {
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('admin@user_level/form', ['vo' => $vo->toArray()]);
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


    //字段验证
    protected function checkData($data)
    {
        $validate = new UserLevelValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }


    public function view()
    {
    	$get_data = $this->request->get();
        
        $vo = UserLevelWordModel::where('user_level_id', $get_data['id'])->where('status',1)->select()->toArray();

        return  $this->fetch('admin@user_level/view', ['vo' => $vo]);
    }

    public function create()
    {
    	$get_data = $this->request->get();

    	$filter_data = UserLevelModel::where('status', 1)->column('title', 'id');
    	$this->assign('filter_level_list', $filter_data);

    	$post_data = $this->request->post();
    	if ($post_data) {
    		$model = new UserLevelWordModel();

    		$validate = new UserLevelWordValidate();

    		if (!$validate->check($post_data)) {
	            $this->error($validate->getError());
	        }

	        if ($model->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
    	}
    	$data['user_level_id'] = $get_data['id'];
    	return  $this->fetch('admin@user_level_word/form', ['vo' => $data, 'type' => 'user_level']);
    }


    public function update()
    {
        $get_data = $this->request->get();


        $all_data = UserLevelWordModel::where('user_level_id', $get_data['id'])->select();
        $id_arr = UserLevelWordModel::where('user_level_id', $get_data['id'])->column('id');

        $post_data = $this->request->post();
        if ($post_data) {
            if ($post_data['user_level_id'] != $get_data['id']) {
                $this->error('数据保存失败, 请稍候再试!');
            }

            if (!isset($post_data['item']) || empty($post_data['item'])) {
                $this->error('请先添加题目设置！');
            }


            foreach ($post_data['item'] as $k => $v) {
                if (!in_array($v['id'], $id_arr)) {
                    $this->error('数据保存失败, 请稍候再试!');
                } else {
                    foreach ($id_arr as $key => $value) {
                        if ($value == $v['id']) {
                            unset($id_arr[$key]);
                        }
                    }
                }
            }

            $model = new UserLevelWordModel();
            $model->saveAll($post_data['item']); 
            if ($id_arr) {
                foreach ($id_arr as $id) {
                    $del_model = UserLevelWordModel::get($id);
                    $del_model->delete();
                }
            }

            $this->success('恭喜, 数据保存成功!', '');

        }

        return  $this->fetch('admin@user_level/update', ['list' => $all_data, 'user_level_id' =>$get_data['id']]);
    }
}