<?php
namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use app\admin\model\UserLevel as UserLevelModel;
use app\admin\model\UserLevelWord as UserLevelWordModel;
use app\admin\validate\UserLevelWord as UserLevelWordValidate;

class UserLevelWord extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'user_level_word';

    public function index()
    {
    	$this->title = '用户难度题目设置';

       	list($get, $db) = [$this->request->get(), new UserLevelWordModel()];

        $db = $db->search($get);

        $this->assign('get', $get);

        $this->userLevel();
       	return parent::_list($db);
    }

    //添加
    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $model = new UserLevelWordModel();
            if ($this->checkData($data) === true && $model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->userLevel();
        
        return  $this->fetch('form', ['vo' => $data, 'type' => 'user_level_word']);
    }

    //编辑
    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = UserLevelWordModel::get($get_data['id']);

        $post_data = $this->request->post();
        if ($post_data) {
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->userLevel();

        return  $this->fetch('form', ['vo' => $vo->toArray(), 'type' => 'user_level_word']);
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

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = UserLevelWordModel::get($data['id']);
            if($model->delete() !== false){
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }


    //字段验证
    protected function checkData($data)
    {
        $validate = new UserLevelWordValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    protected function userLevel()
    {
    	$filter_data = UserLevelModel::where('status', 1)->column('title', 'id');
    	$data = UserLevelModel::column('title', 'id');

    	$this->assign('level_list', $data);
    	$this->assign('filter_level_list', $filter_data);

    }
}