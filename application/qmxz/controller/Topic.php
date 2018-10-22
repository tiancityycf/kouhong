<?php
namespace app\qmxz\controller;

use app\qmxz\model\Topic as TopicModel;
use controller\BasicAdmin;
use think\Db;
use think\facade\Cache;

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

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('qmxz@topic/index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $model               = new TopicModel();
            if ($model->save($data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return $this->fetch('qmxz@topic/form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = TopicModel::get($get_data['id']);

        $post_data = $this->request->post();

        if ($post_data) {
            if ($vo->save($post_data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        return $this->fetch('qmxz@topic/form', ['vo' => $vo->toArray()]);
    }

    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            if (Db::name($this->table)->where('id', $data['id'])->delete()) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    protected function redisSave()
    {
        $redis = Cache::init();

        $topic_list = TopicModel::select();
        if (Cache::set(config('topic_key'), $topic_list)) {
            return true;
        } else {
            return false;
        }

    }
}
