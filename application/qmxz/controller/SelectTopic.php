<?php
namespace app\qmxz\controller;

use app\qmxz\model\SelectTopic as SelectTopicModel;
use controller\BasicAdmin;
use think\Db;
use think\facade\Cache;

class SelectTopic extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'select_topic';

    public function index()
    {
        $this->title = '前台话题';

        list($get, $db) = [$this->request->get(), new SelectTopicModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('qmxz@select_topic/index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $model               = new SelectTopicModel();
            if ($model->save($data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        //话题
        $topic_list = Db::name('topic')->select();
        $topic_arr = [];
        if(!empty($topic_list)){
            foreach ($topic_list as $key => $value) {
                $topic_arr[$value['id']] = $value['title'];
            }
        }
        $this->assign('topic_arr',$topic_arr);

        return $this->fetch('qmxz@select_topic/form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = SelectTopicModel::get($get_data['id']);

        $post_data = $this->request->post();

        if ($post_data) {
            if ($vo->save($post_data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        //话题
        $topic_list = Db::name('topic')->select();
        $topic_arr = [];
        if(!empty($topic_list)){
            foreach ($topic_list as $key => $value) {
                $topic_arr[$value['id']] = $value['title'];
            }
        }
        $this->assign('topic_arr',$topic_arr);
        return $this->fetch('qmxz@select_topic/form', ['vo' => $vo->toArray()]);
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

        $topic_list = SelectTopicModel::select();
        if (Cache::set(config('select_topic_key'), $topic_list)) {
            return true;
        } else {
            return false;
        }

    }
}
