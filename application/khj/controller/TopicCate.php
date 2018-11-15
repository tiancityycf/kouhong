<?php
namespace app\qmxz\controller;

use app\qmxz\model\TopicCate as TopicCateModel;
use controller\BasicAdmin;

class TopicCate extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'topic_cate';

    public function index()
    {
        $this->title = '普通分类';

        list($get, $db) = [$this->request->get(), new TopicCateModel()];

        $db = $db->search($get);

        return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $model               = new TopicCateModel();
            if ($model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = TopicCateModel::get($get_data['id']);

        $post_data = $this->request->post();

        if ($post_data) {
            if ($vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        return $this->fetch('form', ['vo' => $vo->toArray()]);
    }

    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = TopicCateModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }
}
