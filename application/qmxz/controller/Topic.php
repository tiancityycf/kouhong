<?php
namespace app\qmxz\controller;

use app\qmxz\model\Topic as TopicModel;
use controller\BasicAdmin;

class Topic extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'topic';

    public function index()
    {
        $this->title = '话题记录';

        list($get, $db) = [$this->request->get(), new TopicModel()];

        $db = $db->search($get);

        return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $model               = new TopicModel();
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

        $vo = TopicModel::get($get_data['id']);

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
            $model = TopicModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    // protected function redisSave()
    // {
    //     $redis = Cache::init();

    //     $topic_list = TopicModel::select();
    //     if (Cache::set(config('topic_key'), $topic_list)) {
    //         return true;
    //     } else {
    //         return false;
    //     }

    // }
}
