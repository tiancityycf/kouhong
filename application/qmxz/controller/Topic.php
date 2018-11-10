<?php
namespace app\qmxz\controller;

use app\qmxz\model\Topic as TopicModel;
use app\qmxz\model\TopicCate as TopicCateModel;
use app\qmxz\validate\Topic as TopicValidate;
use controller\BasicAdmin;

class Topic extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'topic';

    //字段验证
    protected function checkData($data)
    {
        $validate = new TopicValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title = '普通列表';

        list($get, $db) = [$this->request->get(), new TopicModel()];

        $db = $db->search($get);
        $this->topic_cate();
        return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $model               = new TopicModel();
            if ($this->checkData($data) === true && $model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->topic_cate();
        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = TopicModel::get($get_data['id']);

        $post_data = $this->request->post();

        if ($post_data) {
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->topic_cate();
        return $this->fetch('form', ['vo' => $vo->toArray()]);
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $data = $this->request->post();
        if ($data) {
            $model         = TopicModel::get($data['id']);
            $model->status = 0;
            if ($model->save() !== false) {
                $this->success("禁用成功！", '');
            }
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
        $data = $this->request->post();
        if ($data) {
            $model         = TopicModel::get($data['id']);
            $model->status = 1;
            if ($model->save() !== false) {
                $this->success("启用成功！", '');
            }
        }

        $this->error("启用失败，请稍候再试！");
    }

    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = TopicModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    protected function topic_cate()
    {
        $data = TopicCateModel::column('title', 'id');

        $this->assign('topic_cate', $data);
    }
}
