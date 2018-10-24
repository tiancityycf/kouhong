<?php

namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\model\UserRecord as UserRecordModel;
use app\qmxz\model\UserSpecial as UserSpecialModel;
use app\qmxz\model\UserSpecialWord as UserSpecialWordModel;
use app\qmxz\model\UserSpecialWordCount as UserSpecialWordCountModel;
use app\qmxz\service\Config as ConfigService;
use think\Db;

/**
 * 整点场服务类
 */
class Special
{

    private function getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key] : '';
    }

    /**
     * 获取整点场列表
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function specialList($userId)
    {
        try {
            $list              = SpecialModel::select();
            $user_special_list = UserSpecialModel::where('user_id', $userId)->column('special_id');
            $special_arr       = [];
            $configService = new ConfigService();
            $config_data   = $configService->getAll();
            $answer_time_limit = $this->getConfigValue($config_data, 'answer_time_limit');
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $time_end = $value['display_time'] + $answer_time_limit * 60;
                    $special_arr[$key] = $value;
                    if (in_array($value['id'], $user_special_list) || ($time_end < time())) {
                        $special_arr[$key]['is_pass'] = 1;
                        $special_arr[$key]['remaining_time'] = 0;
                    } else {
                        $special_arr[$key]['is_pass'] = 0;
                        $special_arr[$key]['remaining_time'] = $time_end - time();
                    }
                    //添加选项基数
                    $default_option_base   = $this->getConfigValue($config_data, 'default_option_base');
                    $default_bottom_option = $this->getConfigValue($config_data, 'default_bottom_option');
                    if ($value['num'] < $default_bottom_option) {
                        $special_arr[$key]['num']  = $value['num'] + $default_option_base[0] + $default_option_base[1];
                    }
                }
            }
            return $special_arr;
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取问题列表
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function questionList($data)
    {
        try {
            $display_time = SpecialModel::where('id', $data['special_id'])->value('display_time');
            if ($display_time > time()) {
                return [
                    'status' => 0,
                    'msg'    => '未到时间',
                    'list'   => [],
                ];
            } else {
                $list = SpecialWordModel::where('special_id', $data['special_id'])->select();
                return [
                    'status' => 1,
                    'msg'    => 'ok',
                    'list'   => $list,
                ];
            }
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
                //保存用户记录
                $user_special_word = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('special_word_id', $data['special_word_id'])->find();
                if (!$user_special_word) {
                    $user_special_word                  = new UserSpecialWordModel();
                    $user_special_word->user_id         = $data['user_id'];
                    $user_special_word->special_id      = $data['special_id'];
                    $user_special_word->special_word_id = $data['special_word_id'];
                    $user_special_word->user_select     = $data['user_select'];
                    $user_special_word->create_date     = date('ymd');
                    $user_special_word->create_time     = time();
                    $user_special_word->save();
                } else {
                    return [
                        'status' => 0,
                        'msg'    => '已答过此题',
                    ];
                }

                //答案
                $answer = UserSpecialWordCountModel::where('special_id', $data['special_id'])->where('special_word_id', $data['special_word_id'])->find();
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
                    $answer                  = new UserSpecialWordCountModel();
                    $answer->special_id      = $data['special_id'];
                    $answer->special_word_id = $data['special_word_id'];
                    if ($data['user_select'] == 1) {
                        $answer->left_option  = 1;
                        $answer->right_option = 0;
                        $answer->most_select  = 1;
                    } else {
                        $answer->left_option  = 0;
                        $answer->right_option = 1;
                        $answer->most_select  = 2;
                    }
                    $answer->save();
                }

                $configService = new ConfigService();
                $config_data   = $configService->getAll();
                //消耗金币
                $timing_consume_gold = $this->getConfigValue($config_data, 'timing_consume_gold');
                $user_obj            = UserRecordModel::where('user_id', $data['user_id'])->find();
                $user_obj->gold      = $user_obj->gold - $timing_consume_gold;
                $user_obj->save();

                Db::commit();

                return [
                    'status' => 1,
                    'msg'    => 'ok',
                ];

            } catch (\Exception $e) {
                Db::rollback();
                return [
                    'status' => 2,
                    'msg'    => '系统繁忙',
                ];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 通关请求
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function passAnswer($data)
    {
        try {
            // 开启事务
            Db::startTrans();
            try {
                $user_topic = UserSpecialModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->find();
                if (!$user_topic) {
                    $user_topic              = new UserSpecialModel();
                    $user_topic->user_id     = $data['user_id'];
                    $user_topic->special_id  = $data['special_id'];
                    $user_topic->create_date = date('ymd');
                    $user_topic->create_time = time();
                    $user_topic->save();
                }
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
     * 整点场答题结果接口
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function answerResult($data)
    {
        try {
            $display_time      = SpecialModel::where('id', $data['special_id'])->value('display_time');
            $answer_time_limit = $this->getConfigValue($config_data, 'answer_time_limit');
            $end_time          = $display_time + $answer_time_limit * 60;

            if ($end_time >= time()) {
                $remaining_time    = $end_time - time();
                $user_special_word = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->column('user_id', 'special_id', 'special_word_id', 'user_select');
                if ($user_special_word) {
                    foreach ($user_special_word as $key => $value) {
                        $user_special_word_count                     = UserSpecialWordCountModel::where('special_id', $value['special_id'])->where('special_word_id', $value['special_word_id'])->column('left_option', 'right_option', 'most_select');
                        $user_special_word[$key]['left_option_num']  = $user_special_word_count['left_option'];
                        $user_special_word[$key]['right_option_num'] = $user_special_word_count['right_option'];

                        $special_word                    = SpecialWordModel::get($value['special_word_id']);
                        $user_special_word[$key]['info'] = $special_word;
                    }
                }
                return [
                    'remaining_time' => $remaining_time,
                    'is_end'         => 0,
                    'list'           => $user_special_word,
                ];
            } else {
                $remaining_time    = 0;
                $user_special_word = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->column('user_id', 'special_id', 'special_word_id', 'user_select');
                if ($user_special_word) {
                    foreach ($user_special_word as $key => $value) {
                        $user_special_word_count                     = UserSpecialWordCountModel::where('special_id', $value['special_id'])->where('special_word_id', $value['special_word_id'])->column('left_option', 'right_option', 'most_select');
                        $user_special_word[$key]['left_option_num']  = $user_special_word_count['left_option'];
                        $user_special_word[$key]['right_option_num'] = $user_special_word_count['right_option'];

                        $special_word                    = SpecialWordModel::get($value['special_word_id']);
                        $user_special_word[$key]['info'] = $special_word;
                        if ($value['user_select'] == $user_special_word_count['most_select']) {
                            $user_special_word[$key]['is_correct'] = 1;
                        } else {
                            $user_special_word[$key]['is_correct'] = 0;
                        }
                    }
                }
                return [
                    'remaining_time' => $remaining_time,
                    'is_end'         => 1,
                    'list'           => $user_special_word,
                ];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

}
