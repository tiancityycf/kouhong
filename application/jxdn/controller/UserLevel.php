<?php
namespace app\jxdn\controller;

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
       	return  $this->fetch('admin@user_level/jxdn', $result);
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
        
        return  $this->fetch('admin@user_level/jxdn_form', ['vo' => $data]);
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

        return  $this->fetch('admin@user_level/jxdn_form', ['vo' => $vo->toArray()]);
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

        if ($data['amount_min'] > $data['amount_max']) {
            $this->error("红包金额设置错误！");
        }

        return true;
    }
}