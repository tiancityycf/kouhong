<?php

namespace app\qmxz\controller;

use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\model\UserSpecialWordCount as UserSpecialWordCountModel;
use app\qmxz\validate\SpecialWord as SpecialWordValidate;
use controller\BasicAdmin;
use think\Db;

//整点场下面的题目控制器类
class SpecialWord extends BasicAdmin
{

    //字段验证
    protected function checkData($data)
    {
        $validate = new SpecialWordValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title = '整点题目';

        list($get, $db) = [$this->request->get(), new SpecialWordModel()];

        $db = $db->search($get);

        $this->special_list();
        return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['options']     = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
            $model               = new SpecialWordModel();
            $data['create_time'] = time();

            if ($this->checkData($data) === true && $model->save($data) != false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_list();
        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo        = SpecialWordModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
            $post_data['options'] = json_encode($post_data['options'], JSON_UNESCAPED_UNICODE);
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_list();
        return $this->fetch('edit', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = SpecialWordModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    protected function special_list()
    {
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end   = strtotime(date('Y-m-d 23:59:59', strtotime('+2 day')));
        $data  = SpecialModel::where('display_time', 'between', [$start, $end])->column('title', 'id');

        $this->assign('special_list', $data);
    }

    /**
     * 循环加基数
     * @return [type] [description]
     */
    public function loopAddBase()
    {

        try {
            $topic_word = SpecialWordModel::select();
            $loop_str = Db::name('config')->where('status', 1)->where('index', 'loop_arr')->value('value');
            $loop_arr = json_decode($loop_str);

            foreach ($topic_word as $key => $value) {
                if ($key % 100 == 0) {
                    sleep(1);
                }
                //判断数据是否存在
                $user_topic_word_count = UserSpecialWordCountModel::where('special_id', $value['special_id'])->where('special_word_id', $value['id'])->find();
                if ($user_topic_word_count) {
                    // 开启事务
                    Db::startTrans();
                    try {
                        $options_arr = json_decode($value['options']);
                        $options_num = count($options_arr);

                        if ($options_num > 0) {
                            if (isset($options_arr[0]) && $options_arr[0] != '') {
                                $user_topic_word_count->option1 = $user_topic_word_count->option1 + rand($loop_arr[0], $loop_arr[1]);
                            }
                            if (isset($options_arr[1]) && $options_arr[1] != '') {
                                $user_topic_word_count->option2 = $user_topic_word_count->option2 + rand($loop_arr[0], $loop_arr[1]);
                            }
                            if (isset($options_arr[2]) && $options_arr[2] != '') {
                                $user_topic_word_count->option3 = $user_topic_word_count->option3 + rand($loop_arr[0], $loop_arr[1]);
                            }
                            if (isset($options_arr[3]) && $options_arr[3] != '') {
                                $user_topic_word_count->option4 = $user_topic_word_count->option4 + rand($loop_arr[0], $loop_arr[1]);
                            }
                        }
                        //获取值最多选项
                        $max_arr = [$user_topic_word_count->option1, $user_topic_word_count->option2, $user_topic_word_count->option3, $user_topic_word_count->option4];
                        $max_k   = 1;
                        $max_v   = 0;
                        foreach ($max_arr as $k => $v) {
                            if ($max_v <= $v) {
                                $max_v = $v;
                                $max_k = $k + 1;
                            }
                        }
                        $user_topic_word_count->most_select = $max_k;
                        $user_topic_word_count->save();
                        Db::commit();
                    } catch (\Exception $e) {
                        lg($e);
                        Db::rollback();
                    }
                } else {
                    // 开启事务
                    Db::startTrans();
                    try {
                        $user_topic_word_count                = new UserSpecialWordCountModel();
                        $user_topic_word_count->special_id      = $value['special_id'];
                        $user_topic_word_count->special_word_id = $value['id'];

                        $options_arr = json_decode($value['options']);
                        $options_num = count($options_arr);
                        $max_arr     = [];
                        if ($options_num > 0) {
                            if ($options_num == 1) {
                                $user_topic_word_count->option1 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option2 = 0;
                                $user_topic_word_count->option3 = 0;
                                $user_topic_word_count->option4 = 0;
                            }
                            if ($options_num == 2) {
                                $user_topic_word_count->option1 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option2 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option3 = 0;
                                $user_topic_word_count->option4 = 0;
                            }
                            if ($options_num == 3) {
                                $user_topic_word_count->option1 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option2 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option3 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option4 = 0;
                            }
                            if ($options_num == 4) {
                                $user_topic_word_count->option1 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option2 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option3 = rand($loop_arr[0], $loop_arr[1]);
                                $user_topic_word_count->option4 = rand($loop_arr[0], $loop_arr[1]);
                            }
                        }
                        //获取值最多选项
                        $max_arr = [$user_topic_word_count->option1, $user_topic_word_count->option2, $user_topic_word_count->option3, $user_topic_word_count->option4];
                        $max_k   = 1;
                        $max_v   = 0;
                        foreach ($max_arr as $k => $v) {
                            if ($max_v <= $v) {
                                $max_v = $v;
                                $max_k = $k + 1;
                            }
                        }
                        $user_topic_word_count->most_select = $max_k;
                        $user_topic_word_count->save();
                        Db::commit();
                    } catch (\Exception $e) {
                        lg($e);
                        Db::rollback();
                    }
                }

            }
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];
        } catch (\Exception $e) {
            lg($e);
        }
    }
}
