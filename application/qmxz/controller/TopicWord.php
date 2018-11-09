<?php
namespace app\qmxz\controller;

use app\qmxz\model\Topic as TopicModel;
use app\qmxz\model\TopicWord as TopicWordModel;
use app\qmxz\model\UserTopicWordCount as UserTopicWordCountModel;
use app\qmxz\validate\TopicWord as TopicWordValidate;
use controller\BasicAdmin;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;

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

    public function upload()
    {
        $file = request()->file('file');

        if (!$file) {
            return json_encode(['code' => 0, 'msg' => '请先选择要上传的表格']);
        }

        $info = $file->validate(['ext' => 'xlsx']);
        if ($info) {
            $file_info   = $file->getInfo();
            $reader      = new Xlsx();
            $spreadsheet = $reader->load($file_info['tmp_name']);

            $data = $spreadsheet->getActiveSheet()->toArray();

            $tmp_data = ['topic_id', 'small_label', 'title', 'des', 'options1', 'options2', 'options3', 'options4'];

            $first_data = $data[0];
            if ($tmp_data != $first_data) {
                return json_encode(['code' => 0, 'msg' => '表头和字段对不上！']);
            }

            unset($data[0]);

            //$word_arr = $this->getWordArr();

            $arr = array_chunk($data, 1000);

            $i = 0;
            $j = 0;

            $topic_word_arr = $arr[0];
            $saveData       = [];
            foreach ($topic_word_arr as $key => $value) {
                $i++;
                $options = [];
                foreach ($value as $k => $v) {
                    if (in_array($k, [4, 5, 6, 7])) {
                        if ($v) {
                            $options[] = $v;
                        }
                    } else {
                        $saveData[$key][$tmp_data[$k]] = $v;
                    }
                }
                $saveData[$key]['options']     = json_encode($options, JSON_UNESCAPED_UNICODE);
                $saveData[$key]['create_time'] = time();
            }
            $j                = count($saveData);
            $topic_word_model = new TopicWordModel();
            $topic_word_model->saveAll($saveData);

            return json_encode(['code' => 1, 'msg' => '数据保存成功<font color="green">' . $i . '</font>条，<font color="red">' . $j . '</font>条数据为空或者已经存在']);
        } else {
            return json_encode(['code' => 0, 'msg' => '上传文件格式错误']);
        }
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['create_time'] = time();
            $data['options']     = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
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

    /**
     * 循环加基数
     * @return [type] [description]
     */
    public function loopAddBase()
    {

        // 开启事务
        Db::startTrans();
        try {
            $topic_word = TopicWordModel::select();
            foreach ($topic_word as $key => $value) {
                if ($key % 100 == 0) {
                    sleep(1);
                }
                //判断数据是否存在
                $user_topic_word_count = UserTopicWordCountModel::where('topic_id', $value['topic_id'])->where('topic_word_id', $value['id'])->find();
                if ($user_topic_word_count) {
                    $options_arr = json_decode($value['options']);
                    $options_num = count($options_arr);
                    $max_arr     = [];
                    if ($options_num > 0) {
                        for ($i = 1; $i <= $options_num; $i++) {
                            $user_topic_word_count->option . $i = $user_topic_word_count->option . $i + rand(0, 200);
                            $max_arr[]                          = $user_topic_word_count->option . $i;
                        }
                    }
                    //获取值最多选项
                    $max_k = 1;
                    $max_v = 0;
                    foreach ($max_arr as $k => $v) {
                        if ($max_v <= $v) {
                            $max_v = $v;
                            $max_k = $k + 1;
                        }
                    }
                    $user_topic_word_count->most_select = $max_k;
                    $user_topic_word_count->save();
                } else {
                    $user_topic_word_count                = new UserTopicWordCountModel();
                    $user_topic_word_count->topic_id      = $value['topic_id'];
                    $user_topic_word_count->topic_word_id = $value['id'];

                    $options_arr = json_decode($value['options']);
                    $options_num = count($options_arr);
                    $max_arr     = [];
                    if ($options_num > 0) {
                        for ($i = 1; $i <= $options_num; $i++) {
                            $user_topic_word_count->option . $i = rand(0, 200);
                            $max_arr[]                          = $user_topic_word_count->option . $i;
                        }
                    }
                    //获取值最多选项
                    $max_k = 1;
                    $max_v = 0;
                    foreach ($max_arr as $k => $v) {
                        if ($max_v <= $v) {
                            $max_v = $v;
                            $max_k = $k + 1;
                        }
                    }
                    $user_topic_word_count->most_select = $max_k;
                    $user_topic_word_count->save();
                }

            }
            Db::commit();
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];
        } catch (\Exception $e) {
            lg($e);
            Db::rollback();
        }
    }
}
