<?php
namespace app\qmxz\controller;

use app\qmxz\model\Topic as TopicModel;
use app\qmxz\model\TopicWord as TopicWordModel;
use app\qmxz\validate\TopicWord as TopicWordValidate;
use controller\BasicAdmin;

class TopicWord extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'topic_word';

    //字段验证
    protected function checkData($data)
    {
        $validate = new TopicWordValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title = '普通题目';

        list($get, $db) = [$this->request->get(), new TopicWordModel()];

        $db = $db->search($get);

        $this->topic_list();
        return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $data['options'] = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
            $model               = new TopicWordModel();
            if ($this->checkData($data) === true && $model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        $this->topic_list();
        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = TopicWordModel::get($get_data['id']);

        $post_data = $this->request->post();

        if ($post_data) {
            $post_data['options'] = json_encode($post_data['options'], JSON_UNESCAPED_UNICODE);
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        $this->topic_list();
        return $this->fetch('edit', ['vo' => $vo->toArray()]);
    }

    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = TopicWordModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    // protected function redisSave()
    // {
    //     $redis = Cache::init();

    //     $topic_list = TopicWordModel::select();
    //     if (Cache::set(config('topic_word_key'), $topic_list)) {
    //         return true;
    //     } else {
    //         return false;
    //     }

    // }

    protected function topic_list()
    {
        $data = TopicModel::column('title', 'id');

        $this->assign('topic_list', $data);
    }
}
