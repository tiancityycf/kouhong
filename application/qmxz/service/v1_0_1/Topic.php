<?php

namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\RegretCard as RegretCardModel;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\model\Topic as TopicModel;
use app\qmxz\model\TopicCate as TopicCateModel;
use app\qmxz\model\TopicWord as TopicWordModel;
use app\qmxz\model\User as UserModel;
use app\qmxz\model\UserRecord as UserRecordModel;
use app\qmxz\model\UserSpecialWord as UserSpecialWordModel;
use app\qmxz\model\UserTopic as UserTopicModel;
use app\qmxz\model\UserTopicSmallLabel as UserTopicSmallLabelModel;
use app\qmxz\model\UserTopicWord as UserTopicWordModel;
use app\qmxz\model\UserTopicWordComment as UserTopicWordCommentModel;
use app\qmxz\model\UserTopicWordCount as UserTopicWordCountModel;
use app\qmxz\model\UserTopicWordRecord as UserTopicWordRecordModel;
use think\Db;

/**
 * 普通场服务类
 */
class Topic
{
    protected $configData;

    public function __construct($configData)
    {
        $this->configData = $configData;
    }

    /**
     * 检测金币是否不足接口
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function checkGold($data)
    {
        try {

            $config_data = $this->configData;
            $user_obj    = UserRecordModel::where('user_id', $data['user_id'])->find();
            if (!$user_obj) {
                return [
                    'status' => 2,
                    'msg'    => '该用户不存在',
                ];
            }
            if ($data['type'] == 1) {
                //普通场
                $topic_count          = TopicWordModel::where('topic_id', $data['topic_id'])->count();
                $user_topic_count     = UserTopicWordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->count();
                $user_topic_count     = isset($user_topic_count) ? $user_topic_count : 0;
                $default_consume_gold = $config_data['default_consume_gold'];
                $need_gold            = $default_consume_gold * ($topic_count - $user_topic_count);
                if ($need_gold <= $user_obj->gold) {
                    $is_enough = true;
                } else {
                    $is_enough = false;
                }
            } else {
                //整点场
                $special_count       = SpecialWordModel::where('special_id', $data['topic_id'])->count();
                $user_special_count  = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['topic_id'])->count();
                $user_special_count  = isset($user_special_count) ? $user_special_count : 0;
                $timing_consume_gold = $config_data['timing_consume_gold'];
                $need_gold           = $timing_consume_gold * ($special_count - $user_special_count);
                if ($need_gold <= $user_obj->gold) {
                    $is_enough = true;
                } else {
                    $is_enough = false;
                }
            }

            if ($is_enough) {
                return [
                    'status' => 1,
                    'msg'    => 'ok',
                ];
            } else {
                return [
                    'status' => 0,
                    'msg'    => '金币不足',
                ];
            }

        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 分类接口列表
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function topicCate($userId)
    {
        try {
            return TopicCateModel::select();
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取普通场列表
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function topicList($data)
    {
        try {
            // $list            = SelectTopicModel::select();
            $data['cate_id'] = 4;
            $list            = TopicModel::where('cate_id', $data['cate_id'])->select();
            $user_topic_list = UserTopicModel::where('user_id', $data['user_id'])->where('is_pass', 1)->column('topic_id');
            $config_data     = $this->configData;
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    // $topic_arr            = TopicModel::get($value['topic_id']);
                    // $list[$key]['title']  = $topic_arr['title'];
                    // $list[$key]['des']    = $topic_arr['des'];
                    // $list[$key]['img']    = $topic_arr['img'];
                    // $list[$key]['remark'] = $topic_arr['remark'];

                    // if (in_array($value['topic_id'], $user_topic_list)) {
                    if (in_array($value['id'], $user_topic_list)) {
                        $list[$key]['is_pass'] = 1;
                    } else {
                        $list[$key]['is_pass'] = 0;
                    }
                    //添加选项基数
                    $default_option_base   = $config_data['default_option_base'];
                    $default_bottom_option = $config_data['default_bottom_option'];
                    if ($value['num'] < $default_bottom_option) {
                        $list[$key]['num'] = $value['num'] + $default_option_base[0] + $default_option_base[1];
                    }
                    //小标签
                    $small_label               = UserTopicSmallLabelModel::where('topic_id', $value['id'])->where('user_id', $data['user_id'])->order('correct_num desc')->value('small_label');
                    $list[$key]['small_label'] = isset($small_label) ? $small_label : '暂无';
                }
            }
            return $list;
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取普通场问题列表
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function questionList($data)
    {
        try {
            $topic_word      = TopicWordModel::where('topic_id', $data['topic_id'])->select();
            $user_topic_word = UserTopicWordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->column('topic_word_id');
            if ($topic_word) {
                if (count($topic_word) <= 10) {
                    foreach ($topic_word as $key => $value) {
                        $topic_word[$key]['options'] = json_decode($value['options']);
                        if (in_array($value['id'], $user_topic_word)) {
                            $topic_word[$key]['is_pass']     = 1;
                            $topic_word[$key]['user_select'] = UserTopicWordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->where('topic_word_id', $value['id'])->value('user_select');
                        } else {
                            $topic_word[$key]['is_pass'] = 0;
                        }
                    }
                    return $topic_word;
                } else {
                    $topic_ids = [];
                    foreach ($topic_word as $key => $value) {
                        $topic_ids[] = $key;
                    }
                    $rand_arr  = array_rand($topic_ids, 10);
                    $topic_arr = [];
                    foreach ($rand_arr as $key => $value) {
                        $topic_arr[] = $topic_word[$value];
                    }
                    foreach ($topic_arr as $key => $value) {
                        $topic_arr[$key]['options'] = json_decode($value['options']);
                    }
                    return $topic_arr;
                }
            } else {
                return [];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 反悔卡信息
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function regret_card_info($data)
    {
        try {
            $config_data     = $this->configData;
            $regret_card_arr = $config_data['regret_card_arr'];
            $rand_k          = array_rand($regret_card_arr);
            //反悔说明
            $regret_card_text = $regret_card_arr[$rand_k];
            //用户反悔卡数量
            $openid       = UserModel::where('id', $data['user_id'])->value('openid');
            $regret_times = RegretCardModel::where('openid', $openid)->where('add_date', date('ymd'))->value('times');
            $regret_times = isset($regret_times) ? $regret_times : 0;
            return [
                'regret_card_text' => $regret_card_text,
                'regret_times'     => $regret_times,
            ];
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 普通场亚宝消耗
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function defaultConsumeGold()
    {
        //普通场亚宝消耗
        $config_data = $this->configData;
        //消耗金币
        return $config_data['default_consume_gold'];
    }

    /**
     * 获取普通场评论列表
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function commentList($data)
    {
        try {
            if (isset($data['topic_word_id'])) {
                $list = UserTopicWordCommentModel::where('topic_id', $data['topic_id'])->where('topic_word_id', $data['topic_word_id'])->order('create_time desc')->select();
            } else {
                $list = UserTopicWordCommentModel::where('topic_id', $data['topic_id'])->order('create_time desc')->select();
            }
            $user_info = UserModel::where('id', 11)->find();
            if ($list) {
                foreach ($list as $key => $value) {
                    $user_info              = UserModel::where('id', $value['user_id'])->find();
                    $list[$key]['nickname'] = $user_info['nickname'];
                    $list[$key]['avatar']   = $user_info['avatar'];
                }
            } else {
                $list = [];
            }
            return $list;
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取问题相关信息
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function getQuestion($data)
    {
        try {
            $info = TopicWordModel::where('topic_id', $data['topic_id'])->where('id', $data['topic_word_id'])->find();
            if ($info) {
                $info['options']     = json_decode($info['options']);
                $info['user_select'] = $data['user_select'];
            }

            return $info;
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 提交问题答案
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function submitAnswer($data)
    {
        try {
            // 开启事务
            Db::startTrans();
            try {
                //是否已打过此题
                // $user_topic_word = UserTopicWordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->where('topic_word_id')->find();
                // if (!$user_topic_word) {
                //添加普通场参与人数
                if ($data['is_pass'] == 1) {
                    // $select_topic = SelectTopicModel::where('topic_id', $data['topic_id'])->find();
                    $select_topic = TopicModel::where('id', $data['topic_id'])->find();
                    if (!$select_topic) {
                        return [
                            'status' => 0,
                            'msg'    => '不存在该话题',
                        ];
                    }
                    $select_topic->num = $select_topic->num + 1;
                    $select_topic->save();
                }
                //保存普通场记录
                $user_topic = UserTopicModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->find();
                if ($user_topic) {
                    if (($user_topic['is_pass'] != 1) && ($data['is_pass'] == 1)) {
                        $user_topic->is_pass = 1;
                        $user_topic->save();
                    }
                } else {
                    $user_topic              = new UserTopicModel();
                    $user_topic->user_id     = $data['user_id'];
                    $user_topic->topic_id    = $data['topic_id'];
                    $user_topic->create_date = date('ymd');
                    $user_topic->create_time = time();
                    if ($data['is_pass'] == 1) {
                        $user_topic->is_pass = 1;
                    }
                    $user_topic->save();
                }

                //保存用户普通场记录
                $user_topic_word                = new UserTopicWordModel();
                $user_topic_word->user_id       = $data['user_id'];
                $user_topic_word->topic_id      = $data['topic_id'];
                $user_topic_word->topic_word_id = $data['topic_word_id'];
                $user_topic_word->user_select   = $data['user_select'];
                $user_topic_word->create_date   = date('ymd');
                $user_topic_word->create_time   = time();
                $user_topic_word->save();

                //答案
                $answer = UserTopicWordCountModel::where('topic_id', $data['topic_id'])->where('topic_word_id', $data['topic_word_id'])->find();
                if ($answer) {
                    if ($data['user_select'] == 1) {
                        $answer->option1 = $answer->option1 + 1;
                    }
                    if ($data['user_select'] == 2) {
                        $answer->option2 = $answer->option2 + 1;
                    }
                    if ($data['user_select'] == 3) {
                        $answer->option3 = $answer->option3 + 1;
                    }
                    if ($data['user_select'] == 4) {
                        $answer->option4 = $answer->option4 + 1;
                    }
                    //获取值最多选项
                    $max_arr = [$answer->option1, $answer->option2, $answer->option3, $answer->option4];
                    $max_k   = 1;
                    $max_v   = 0;
                    foreach ($max_arr as $k => $v) {
                        if ($max_v <= $v) {
                            $max_v = $v;
                            $max_k = $k + 1;
                        }
                    }
                    $answer->most_select = $max_k;
                    $answer->save();
                } else {
                    $answer                = new UserTopicWordCountModel();
                    $answer->topic_id      = $data['topic_id'];
                    $answer->topic_word_id = $data['topic_word_id'];
                    if ($data['user_select'] == 1) {
                        $answer->option1     = 1;
                        $answer->option2     = 0;
                        $answer->option3     = 0;
                        $answer->option4     = 0;
                        $answer->most_select = 1;
                    }
                    if ($data['user_select'] == 2) {
                        $answer->option1     = 0;
                        $answer->option2     = 1;
                        $answer->option3     = 0;
                        $answer->option4     = 0;
                        $answer->most_select = 2;
                    }
                    if ($data['user_select'] == 3) {
                        $answer->option1     = 0;
                        $answer->option2     = 0;
                        $answer->option3     = 1;
                        $answer->option4     = 0;
                        $answer->most_select = 3;
                    }
                    if ($data['user_select'] == 4) {
                        $answer->option1     = 0;
                        $answer->option2     = 0;
                        $answer->option3     = 0;
                        $answer->option4     = 1;
                        $answer->most_select = 4;
                    }
                    $answer->save();
                }

                $config_data = $this->configData;
                //消耗金币
                $default_consume_gold = $config_data['default_consume_gold'];
                $user_obj             = UserRecordModel::where('user_id', $data['user_id'])->find();
                if ($data['user_select'] == $answer['most_select']) {
                    $get_gold_one = $config_data['get_gold_one'];
                } else {
                    $get_gold_one = 0;
                }
                //修改金币
                $user_obj->gold = $user_obj->gold + ($get_gold_one - $default_consume_gold);
                $user_obj->save();
                //总参与人数
                $participants_num = $answer->option1 + $answer->option2 + $answer->option3 + $answer->option4;

                //添加选项基数
                $default_option_base   = $config_data['default_option_base'];
                $default_bottom_option = $config_data['default_bottom_option'];
                if ($participants_num <= $default_bottom_option) {
                    $option1 = $answer->option1 + $default_option_base[0];
                    $option2 = $answer->option2 + $default_option_base[1];
                    $option3 = $answer->option3 + $default_option_base[2];
                    $option4 = $answer->option4 + $default_option_base[3];
                } else {
                    $option1 = $answer->option1;
                    $option2 = $answer->option2;
                    $option3 = $answer->option3;
                    $option4 = $answer->option4;
                }

                Db::commit();
                //判断选项个数
                $question_options       = TopicWordModel::where('topic_id', $data['topic_id'])->where('id', $data['topic_word_id'])->value('options');
                $question_options_count = count(json_decode($question_options));
                switch ($question_options_count) {
                    case '1':
                        $options = [$option1];
                        break;

                    case '2':
                        $options = [$option1, $option2];
                        break;

                    case '3':
                        $options = [$option1, $option2, $option3];
                        break;

                    case '4':
                        $options = [$option1, $option2, $option3, $option4];
                        break;
                }

                //保存用户答对答错情况记录
                $user_topic_word_record = UserTopicWordRecordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->where('topic_word_id', $data['topic_word_id'])->find();
                if ($user_topic_word_record) {
                    if ($data['user_select'] == $answer->most_select) {
                        $user_topic_word_record->is_correct = 1;
                    } else {
                        $user_topic_word_record->is_correct = 0;
                    }
                    $user_topic_word_record->save();
                } else {
                    $user_topic_word_record                = new UserTopicWordRecordModel();
                    $user_topic_word_record->user_id       = $data['user_id'];
                    $user_topic_word_record->topic_id      = $data['topic_id'];
                    $user_topic_word_record->topic_word_id = $data['topic_word_id'];
                    $user_topic_word_record->dday          = date('Ymd');
                    if ($data['user_select'] == $answer->most_select) {
                        $user_topic_word_record->is_correct = 1;
                    } else {
                        $user_topic_word_record->is_correct = 0;
                    }
                    $user_topic_word_record->save();
                }

                //保存用户小标签记录
                $small_label            = TopicWordModel::where('topic_id', $data['topic_id'])->where('id', $data['topic_word_id'])->value('small_label');
                $user_topic_small_label = UserTopicSmallLabelModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->where('topic_word_id', $data['topic_word_id'])->where('small_label', $small_label)->find();
                if ($user_topic_small_label) {
                    if ($user_topic_word_record->is_correct == 1) {
                        $user_topic_small_label->correct_num = $user_topic_small_label->correct_num + 1;
                    } else {
                        $user_topic_small_label->error_num = $user_topic_small_label->error_num + 1;
                    }
                    $user_topic_small_label->save();
                } else {
                    $user_topic_small_label                = new UserTopicSmallLabelModel();
                    $user_topic_small_label->user_id       = $data['user_id'];
                    $user_topic_small_label->topic_id      = $data['topic_id'];
                    $user_topic_small_label->topic_word_id = $data['topic_word_id'];
                    $user_topic_small_label->small_label   = $small_label;
                    if ($user_topic_word_record->is_correct == 1) {
                        $user_topic_small_label->correct_num = 1;
                        $user_topic_small_label->error_num   = 0;
                    } else {
                        $user_topic_small_label->correct_num = 0;
                        $user_topic_small_label->error_num   = 1;
                    }
                    $user_topic_small_label->save();
                }

                //随机提示语
                //正确提示语
                $topic_correct_arr = $config_data['topic_correct_arr'];
                $c_k               = array_rand($topic_correct_arr);
                $correct_tip       = $topic_correct_arr[$c_k];
                //错误提示语
                $topic_error_arr = $config_data['topic_error_arr'];
                $e_k             = array_rand($topic_error_arr);
                $error_tip       = $topic_error_arr[$e_k];

                //随机输入提示语
                $input_tip_arr = $config_data['input_tip_arr'];
                $i_k           = array_rand($input_tip_arr);
                $input_tip     = $input_tip_arr[$i_k];

                return [
                    'status'      => 1,
                    'msg'         => 'ok',
                    'correct_tip' => $correct_tip,
                    'error_tip'   => $error_tip,
                    'input_tip'   => $input_tip,
                    'most_select' => $answer->most_select,
                    'options'     => $options,
                    'gold'        => $get_gold_one,
                ];
                // } else {
                //     return [
                //         'status' => 0,
                //         'msg'    => '已答过此题',
                //     ];
                // }
            } catch (\Exception $e) {
                Db::rollback();
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 用户提交评论接口
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function submitComment($data)
    {
        try {
            // 开启事务
            Db::startTrans();
            try {
                $comment_obj                = new UserTopicWordCommentModel();
                $comment_obj->user_id       = $data['user_id'];
                $comment_obj->topic_id      = $data['topic_id'];
                $comment_obj->topic_word_id = $data['topic_word_id'];
                $comment_obj->user_comment  = $data['user_comment'];
                $comment_obj->create_date   = date('ymd');
                $comment_obj->create_time   = time();
                $comment_obj->save();
                Db::commit();
                return [
                    'status' => 1,
                    'msg'    => 'ok',
                ];
            } catch (\Exception $e) {
                Db::rollback();
                return [
                    'status' => 0,
                    'msg'    => 'fail',
                ];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取用户话题答题结果
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function userTopicResult($data)
    {
        try {
            $user_topic_word_record = UserTopicWordRecordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->select();
            if (count($user_topic_word_record) > 0) {
                $all_num     = 0;
                $correct_num = 0;
                $error_num   = 0;
                foreach ($user_topic_word_record as $key => $value) {
                    $all_num++;
                    if ($value['is_correct'] == 1) {
                        $correct_num++;
                    } else {
                        $error_num++;
                    }
                }
            } else {
                $all_num     = 0;
                $correct_num = 0;
                $error_num   = 0;
            }

            return [
                'all_num'     => $all_num,
                'correct_num' => $correct_num,
                'error_num'   => $error_num,
            ];
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }
}
