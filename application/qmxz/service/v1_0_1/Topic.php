<?php

namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\SelectTopic as SelectTopicModel;
use app\qmxz\model\Topic as TopicModel;
use app\qmxz\model\TopicWord as TopicWordModel;
use app\qmxz\model\UserRecord as UserRecordModel;
use app\qmxz\model\UserTopic as UserTopicModel;
use app\qmxz\model\UserTopicWord as UserTopicWordModel;
use app\qmxz\model\UserTopicWordComment as UserTopicWordCommentModel;
use app\qmxz\model\UserTopicWordCount as UserTopicWordCountModel;
use app\qmxz\service\Config as ConfigService;
use think\Db;

/**
 * 普通场服务类
 */
class Topic
{

    private function getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key] : '';
    }

    /**
     * 检测金币是否不足接口
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function checkGold($data)
    {
        try {
            $configService = new ConfigService();
            $config_data   = $configService->getAll();
            $user_obj      = UserRecordModel::where('user_id', $data['user_id'])->find();
            if (!$user_obj) {
                return [
                    'status' => 2,
                    'msg'    => '该用户不存在',
                ];
            }
            if ($data['type'] == 1) {
                //普通场
                $default_gold_limit = $this->getConfigValue($config_data, 'default_gold_limit');
                if ($default_gold_limit <= $user_obj->gold) {
                    $is_enough = true;
                } else {
                    $is_enough = false;
                }
            } else {
                //整点场
                $timing_gold_limit = $this->getConfigValue($config_data, 'timing_gold_limit');
                if ($timing_gold_limit <= $user_obj->gold) {
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
     * 获取普通场列表
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function topicList($userId)
    {
        try {
            $list            = SelectTopicModel::select();
            $user_topic_list = UserTopicModel::where('user_id', $userId)->column('topic_id');
            $configService   = new ConfigService();
            $config_data     = $configService->getAll();
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $topic_arr           = TopicModel::get($value['topic_id']);
                    $list[$key]['title'] = $topic_arr['title'];
                    $list[$key]['des']   = $topic_arr['des'];
                    $list[$key]['img']   = $topic_arr['img'];
                    if (in_array($value['topic_id'], $user_topic_list)) {
                        $list[$key]['is_pass'] = 1;
                    } else {
                        $list[$key]['is_pass'] = 0;
                    }
                    //添加选项基数
                    $default_option_base   = $this->getConfigValue($config_data, 'default_option_base');
                    $default_bottom_option = $this->getConfigValue($config_data, 'default_bottom_option');
                    if ($value['num'] < $default_bottom_option) {
                        $list[$key]['num'] = $value['num'] + $default_option_base[0] + $default_option_base[1];
                    }
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
                foreach ($topic_word as $key => $value) {
                    if (in_array($value['id'], $user_topic_word)) {
                        $topic_word[$key]['is_pass'] = 1;
                    } else {
                        $topic_word[$key]['is_pass'] = 0;
                    }
                }
            }
            return $topic_word;
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取普通场评论列表
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function commentList($data)
    {
        try {
            return UserTopicWordCommentModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->select();
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
                $user_topic_word = UserTopicWordModel::where('user_id', $data['user_id'])->where('topic_id', $data['topic_id'])->where('topic_word_id')->find();
                if (!$user_topic_word) {
                    //添加普通场参与人数
                    if ($data['is_pass'] == 1) {
                        $select_topic = SelectTopicModel::where('topic_id', $data['topic_id'])->find();
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
                            $answer->left_option = $answer->left_option + 1;
                        } else {
                            $answer->right_option = $answer->right_option + 1;
                        }
                        if ($answer->left_option > $answer->right_option) {
                            $answer->most_select = 1;
                        } else {
                            $answer->most_select = 2;
                        }
                        $answer->save();
                    } else {
                        $answer                = new UserTopicWordCountModel();
                        $answer->topic_id      = $data['topic_id'];
                        $answer->topic_word_id = $data['topic_word_id'];
                        if ($data['user_select'] == 1) {
                            $answer->left_option  = 1;
                            $answer->right_option = 0;
                        } else {
                            $answer->left_option  = 0;
                            $answer->right_option = 1;
                        }
                        if ($answer->left_option > $answer->right_option) {
                            $answer->most_select = 1;
                        } else {
                            $answer->most_select = 2;
                        }
                        $answer->save();
                    }

                    $configService = new ConfigService();
                    $config_data   = $configService->getAll();
                    //消耗金币
                    $default_consume_gold = $this->getConfigValue($config_data, 'default_consume_gold');
                    $user_obj             = UserRecordModel::where('user_id', $data['user_id'])->find();
                    if ($data['user_select'] == $answer['most_select']) {
                        $get_gold_one = $this->getConfigValue($config_data, 'get_gold_one');
                    } else {
                        $get_gold_one = 0;
                    }
                    //修改金币
                    $user_obj->gold = $user_obj->gold + ($get_gold_one - $default_consume_gold);
                    $user_obj->save();
                    //总参与人数
                    $participants_num = $answer->left_option + $answer->right_option;

                    //添加选项基数
                    $default_option_base   = $this->getConfigValue($config_data, 'default_option_base');
                    $default_bottom_option = $this->getConfigValue($config_data, 'default_bottom_option');
                    if ($participants_num <= $default_bottom_option) {
                        $left_option  = $answer->left_option + $default_option_base[0];
                        $right_option = $answer->right_option + $default_option_base[1];
                    } else {
                        $left_option  = $answer->left_option;
                        $right_option = $answer->right_option;
                    }

                    Db::commit();

                    return [
                        'status' => 1,
                        'msg'    => 'ok',
                        'left'   => $left_option,
                        'right'  => $right_option,
                        'gold'   => $get_gold_one,
                    ];
                } else {
                    return [
                        'status' => 0,
                        'msg'    => '已答过此题',
                    ];
                }
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
}
