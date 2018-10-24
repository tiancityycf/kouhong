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
            $user_special_list = UserSpecialModel::where('user_id', $userId)->where('is_pass', 1)->column('special_id');
            $special_arr       = [];
            $configService     = new ConfigService();
            $config_data       = $configService->getAll();
            $answer_time_limit = $this->getConfigValue($config_data, 'answer_time_limit');
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $time_end          = $value['display_time'] + $answer_time_limit * 60;
                    $special_arr[$key] = $value;
                    if (in_array($value['id'], $user_special_list) || ($time_end < time())) {
                        $special_arr[$key]['is_pass']        = 1;
                        $special_arr[$key]['remaining_time'] = 0;
                    } else {
                        $special_arr[$key]['is_pass']        = 0;
                        $special_arr[$key]['remaining_time'] = $time_end - time();
                    }
                    //添加选项基数
                    $default_option_base   = $this->getConfigValue($config_data, 'default_option_base');
                    $default_bottom_option = $this->getConfigValue($config_data, 'default_bottom_option');
                    if ($value['num'] < $default_bottom_option) {
                        $special_arr[$key]['num'] = $value['num'] + $default_option_base[0] + $default_option_base[1];
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
                $list              = SpecialWordModel::where('special_id', $data['special_id'])->select();
                $user_special_word = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->column('special_word_id');
                if ($list) {
                    foreach ($list as $key => $value) {
                        if (in_array($value['id'], $user_special_word)) {
                            $list[$key]['is_pass'] = 1;
                        } else {
                            $list[$key]['is_pass'] = 0;
                        }
                    }
                }
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
                //添加普通场参与人数
                if ($data['is_pass'] == 1) {
                    $special = SpecialModel::where('id', $data['special_id'])->find();
                    if (!$special) {
                        return [
                            'status' => 0,
                            'msg'    => '不存在该话题',
                        ];
                    }
                    $special->num = $special->num + 1;
                    $special->save();
                }
                //保存普通场记录
                $user_special = UserSpecialModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->find();
                if ($user_special) {
                    if (($user_special['is_pass'] != 1) && ($data['is_pass'] == 1)) {
                        $user_special->is_pass = 1;
                        $user_special->save();
                    }
                } else {
                    $user_special              = new UserSpecialModel();
                    $user_special->user_id     = $data['user_id'];
                    $user_special->special_id  = $data['special_id'];
                    $user_special->create_date = date('ymd');
                    $user_special->create_time = time();
                    if ($data['is_pass'] == 1) {
                        $user_special->is_pass = 1;
                    }
                    $user_special->save();
                }

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
                }
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
     * 整点场答题结果接口
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function answerResult($data)
    {
        try {
            $display_time      = SpecialModel::where('id', $data['special_id'])->value('display_time');
            $configService     = new ConfigService();
            $config_data       = $configService->getAll();
            $answer_time_limit = $this->getConfigValue($config_data, 'answer_time_limit');
            $end_time          = $display_time + ($answer_time_limit - 10) * 60;

            $special_word = SpecialWordModel::where('special_id', $data['special_id'])->select();
            ///添加选项基数
            $default_option_base   = $this->getConfigValue($config_data, 'default_option_base');
            $default_bottom_option = $this->getConfigValue($config_data, 'default_bottom_option');
            foreach ($special_word as $key => $value) {
                //判断参与人数是否加基数
                $user_special_word_count = UserSpecialWordCountModel::where('special_id', $value['special_id'])->where('special_word_id', $value['id'])->find();
                $participants_num        = $user_special_word_count['left_option'] + $user_special_word_count['right_option'];
                if ($participants_num <= $default_bottom_option) {
                    $special_word[$key]['left_option_num']  = $user_special_word_count['left_option'] + $default_option_base[0];
                    $special_word[$key]['right_option_num'] = $user_special_word_count['right_option'] + $default_option_base[1];
                } else {
                    $special_word[$key]['left_option_num']  = $user_special_word_count['left_option'];
                    $special_word[$key]['right_option_num'] = $user_special_word_count['right_option'];
                }
                //得到大多数选项
                if ($special_word[$key]['left_option_num'] >= $special_word[$key]['right_option_num']) {
                    $special_word[$key]['most_select'] = 1;
                } else {
                    $special_word[$key]['most_select'] = 2;
                }
                //用户选项
                $user_select                       = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $value['special_id'])->where('special_word_id', $value['id'])->value('user_select');
                $special_word[$key]['user_select'] = isset($user_select) ? $user_select : 0;
                if (($user_select != 0) && ($user_select == $special_word[$key]['most_select'])) {
                    $special_word[$key]['is_correct'] = 1;
                } else {
                    $special_word[$key]['is_correct'] = 0;
                }
            }
            //计算答对题目数
            $correct_num = 0;
            foreach ($special_word as $key => $value) {
                if ($value['is_correct'] == 1) {
                    $correct_num++;
                }
            }
            //判断是否已到结束时间
            if ($end_time >= time()) {
                $remaining_time = $end_time - time();
                $is_end         = 0;
            } else {
                $remaining_time = 0;
                $is_end         = 1;
            }
            //答对多少题
            if ($correct_num >= count($special_word)) {
                //生成兑换码
                $code = UserSpecialRedeemcodeModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->value('code');
                if (!isset($code)) {
                    $code                                = date('ymd', $display_time) . uniqid();
                    $user_special_redeemcode             = new UserSpecialRedeemcodeModel();
                    $user_special_redeemcode->user_id    = $data['user_id'];
                    $user_special_redeemcode->special_id = $data['special_id'];
                    $user_special_redeemcode->code       = $code;
                    $user_special_redeemcode->save();
                }

                return [
                    'remaining_time' => $remaining_time,
                    'is_end'         => 1,
                    'total_num'      => count($special_word),
                    'correct_num'    => $correct_num,
                    'code'           => $code,
                    'list'           => $special_word,
                ];
            } else {
                return [
                    'remaining_time' => $remaining_time,
                    'is_end'         => 1,
                    'total_num'      => count($special_word),
                    'correct_num'    => $correct_num,
                    'list'           => $special_word,
                ];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

}
