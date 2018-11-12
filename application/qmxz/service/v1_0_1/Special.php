<?php

namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\Address as AddressModel;
use app\qmxz\model\RegretCard as RegretCardModel;
use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\SpecialGold as SpecialGoldModel;
use app\qmxz\model\SpecialPrize as SpecialPrizeModel;
use app\qmxz\model\SpecialWarehouse as SpecialWarehouseModel;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\model\SpecialWordWarehouse as SpecialWordWarehouseModel;
use app\qmxz\model\TemplateInfo as TemplateInfoModel;
use app\qmxz\model\User as UserModel;
use app\qmxz\model\UserRecord as UserRecordModel;
use app\qmxz\model\UserSpecial as UserSpecialModel;
use app\qmxz\model\UserSpecialPrize as UserSpecialPrizeModel;
use app\qmxz\model\UserSpecialRedeemcode as UserSpecialRedeemcodeModel;
use app\qmxz\model\UserSpecialWord as UserSpecialWordModel;
use app\qmxz\model\UserSpecialWordComment as UserSpecialWordCommentModel;
use app\qmxz\model\UserSpecialWordCount as UserSpecialWordCountModel;
use think\cache\driver\Redis;
use think\Db;
use think\facade\Config;

/**
 * 整点场服务类
 */
class Special
{
    protected $configData;

    public function __construct($configData)
    {
        $this->configData = $configData;
    }

    /**
     * 获取整点场列表
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function specialList($userId)
    {
        try {
            $start             = strtotime(date('Y-m-d 00:00:00'));
            $end               = strtotime(date('Y-m-d 23:59:59'));
            $list              = SpecialModel::where('display_time', 'between', [$start, $end])->order('display_time')->select();
            $user_special_list = UserSpecialModel::where('user_id', $userId)->where('create_date', date('ymd'))->where('is_pass', 1)->column('special_id');
            $special_arr       = [];
            $config_data       = $this->configData;
            $answer_time_limit = $config_data['answer_time_limit'];
            $special_rule      = $config_data['special_rule'];
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $list[$key]['start_time'] = date('H:i', $value['display_time']);
                    $list[$key]['des']        = date('H:i', $value['display_time']) . '-' . date('H:i', $value['display_time'] + $answer_time_limit * 60);
                    $time_end                 = $value['display_time'] + $answer_time_limit * 60;
                    $special_arr[$key]        = $value;
                    if (in_array($value['id'], $user_special_list)) {
                        $special_arr[$key]['is_pass'] = 1;
                        if ($time_end < time()) {
                            $special_arr[$key]['remaining_time'] = 0;
                        } else {
                            $special_arr[$key]['remaining_time'] = $time_end - time();
                        }

                    } else {
                        $special_arr[$key]['is_pass'] = 0;
                        if ($value['display_time'] <= time()) {
                            if ($time_end < time()) {
                                $special_arr[$key]['remaining_time'] = 0;
                            } else {
                                $special_arr[$key]['remaining_time'] = $time_end - time();
                            }
                            // $special_arr[$key]['remaining_time'] = $time_end - time();
                        } else {
                            $special_arr[$key]['remaining_time'] = $answer_time_limit * 60;
                        }

                    }

                    // $special_arr[$key]['dday'] = date('Y-m-d H:i:s', $value['display_time']);

                    //添加选项基数
                    $default_option_base   = $config_data['default_option_base'];
                    $default_bottom_option = $config_data['default_bottom_option'];
                    if ($value['num'] < $default_bottom_option) {
                        $special_arr[$key]['num'] = $value['num'] + $default_option_base[0] + $default_option_base[1];
                    }
                    $prize_info                        = SpecialPrizeModel::get($value['prize_id']);
                    $special_arr[$key]['prize_name']   = $prize_info['name'];
                    $special_arr[$key]['prize_img']    = $prize_info['img'];
                    $special_arr[$key]['banners']      = json_decode($value['banners']);
                    $special_arr[$key]['special_rule'] = $special_rule;
                }
                foreach ($list as $key => $value) {
                    if (($value['display_time'] + $answer_time_limit * 60) <= time()) {
                        $list[$key]['is_end'] = 1;
                    } else {
                        $list[$key]['is_end'] = 0;
                    }
                }
                foreach ($list as $key => $value) {
                    if ($value['display_time'] < time() && ($list[$key]['is_end'] == 0)) {
                        $list[$key]['curr'] = 1;
                        // $list[$key]['curr_start'] = $value['display_time'] - time();
                    }
                    if ($value['display_time'] > time()) {
                        $list[$key]['next']       = 1;
                        $list[$key]['next_start'] = $value['display_time'] - time();
                        break;
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
     * 进入答题页扣除金币接口
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function deductGold($data)
    {
        try {
            $user_special = UserSpecialModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->find();
            if (!$user_special) {
                // 开启事务
                Db::startTrans();
                try {
                    //添加普通场参与人数
                    $special = SpecialModel::where('id', $data['special_id'])->find();
                    if (!$special) {
                        return [
                            'status' => 0,
                            'msg'    => '不存在该话题',
                        ];
                    }
                    $special->num = $special->num + 1;
                    $special->save();

                    //保存用户整点场记录
                    $user_special              = new UserSpecialModel();
                    $user_special->user_id     = $data['user_id'];
                    $user_special->special_id  = $data['special_id'];
                    $user_special->create_date = date('ymd');
                    $user_special->create_time = time();
                    $user_special->save();

                    //扣除金币
                    $special_count       = SpecialWordModel::where('special_id', $data['special_id'])->count();
                    $user_special_count  = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->count();
                    $user_special_count  = isset($user_special_count) ? $user_special_count : 0;
                    $config_data         = $this->configData;
                    $timing_consume_gold = $config_data['timing_consume_gold'];
                    $need_gold           = $timing_consume_gold * ($special_count - $user_special_count);
                    $user_record         = UserRecordModel::where('user_id', $data['user_id'])->find();
                    $user_record->gold   = $user_record->gold - $need_gold;
                    $user_record->save();

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

            } else {
                return [
                    'status' => 1,
                    'msg'    => 'ok',
                ];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 整点场轮播图
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function specialBanners($data)
    {
        $banners     = SpecialModel::where('id', $data['special_id'])->value('banners');
        $banners_arr = json_decode($banners);
        return $banners_arr;
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
                //结束时间
                $config_data       = $this->configData;
                $answer_time_limit = $config_data['answer_time_limit'];
                $time_end          = $display_time + $answer_time_limit * 60 - time();
                $time_end          = $time_end > 0 ? $time_end : 0;

                $list              = SpecialWordModel::where('special_id', $data['special_id'])->select();
                $user_special_word = UserSpecialWordModel::where('user_id', $data['user_id'])->where('create_date', date('ymd'))->where('special_id', $data['special_id'])->column('special_word_id');
                if ($list) {
                    if (count($list) <= 10) {
                        foreach ($list as $key => $value) {
                            if (in_array($value['id'], $user_special_word)) {
                                $list[$key]['is_pass'] = 1;
                            } else {
                                $list[$key]['is_pass'] = 0;
                            }
                            $list[$key]['options']     = json_decode($value['options']);
                            $user_select               = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('special_word_id', $value['id'])->value('user_select');
                            $list[$key]['user_select'] = isset($user_select) ? $user_select : 0;
                        }
                        $special_arr = $list;
                    } else {
                        $topic_ids = [];
                        foreach ($list as $key => $value) {
                            $topic_ids[] = $key;
                        }
                        $rand_arr  = array_rand($topic_ids, 10);
                        $topic_arr = [];
                        foreach ($rand_arr as $key => $value) {
                            $topic_arr[] = $list[$value];
                        }
                        foreach ($topic_arr as $key => $value) {
                            $topic_arr[$key]['options']     = json_decode($value['options']);
                            $user_select                    = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('special_word_id', $value['id'])->value('user_select');
                            $topic_arr[$key]['user_select'] = isset($user_select) ? $user_select : 0;
                        }
                        $special_arr = $topic_arr;
                    }

                }
                return [
                    'status'         => 1,
                    'msg'            => 'ok',
                    'remaining_time' => $time_end,
                    'list'           => $special_arr,
                ];
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
     * 输入框提示语
     * @return [type]       [description]
     */
    public function input_text()
    {
        //普通场亚宝消耗
        $config_data   = $this->configData;
        $input_tip_arr = $config_data['input_tip_arr'];
        $rand_k        = array_rand($input_tip_arr);
        $input_text    = $input_tip_arr[$rand_k];
        //消耗金币
        return $input_text;
    }

    /**
     * 获取普通场评论列表
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function commentList($data)
    {
        try {
            if (isset($data['special_word_id'])) {
                $list = UserSpecialWordCommentModel::where('special_id', $data['special_id'])->where('special_word_id', $data['special_word_id'])->order('create_time desc')->select();
            } else {
                $list = UserSpecialWordCommentModel::where('special_id', $data['special_id'])->order('create_time desc')->select();
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
                $comment_obj                  = new UserSpecialWordCommentModel();
                $comment_obj->user_id         = $data['user_id'];
                $comment_obj->special_id      = $data['special_id'];
                $comment_obj->special_word_id = $data['special_word_id'];
                $comment_obj->user_comment    = $data['user_comment'];
                $comment_obj->create_date     = date('ymd');
                $comment_obj->create_time     = time();
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
     * 整点场亚宝消耗
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function timing_consume_gold()
    {
        //普通场亚宝消耗
        $config_data = $this->configData;
        //消耗金币
        return $config_data['timing_consume_gold'];
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
                //保存普通场记录
                $user_special = UserSpecialModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('create_date', date('ymd'))->find();
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
                $user_special_word = UserSpecialWordModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('create_date', date('ymd'))->where('special_word_id', $data['special_word_id'])->find();
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
                        $answer                  = new UserSpecialWordCountModel();
                        $answer->special_id      = $data['special_id'];
                        $answer->special_word_id = $data['special_word_id'];
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
                } else {
                    $user_special_word->user_select = $data['user_select'];
                    $user_special_word->create_time = time();
                    $user_special_word->save();
                }
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
     * 整点场答题结果接口
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function answerResult($data)
    {
        try {
            $display_time = SpecialModel::where('id', $data['special_id'])->value('display_time');

            $config_data       = $this->configData;
            $answer_time_limit = $config_data['answer_time_limit'];
            $end_time          = $display_time + $answer_time_limit * 60;

            $special_word = SpecialWordModel::where('special_id', $data['special_id'])->select();
            ///添加选项基数
            $default_option_base   = $config_data['default_option_base'];
            $default_bottom_option = $config_data['default_bottom_option'];
            foreach ($special_word as $key => $value) {
                $special_word[$key]['options'] = json_decode($value['options']);
                //判断参与人数是否加基数
                $user_special_word_count = UserSpecialWordCountModel::where('special_id', $value['special_id'])->where('special_word_id', $value['id'])->find();
                $participants_num        = $user_special_word_count['option1'] + $user_special_word_count['option2'] + $user_special_word_count['option3'] + $user_special_word_count['option4'];
                if ($participants_num <= $default_bottom_option) {
                    $option1_num = $user_special_word_count['option1'] + $default_option_base[0];
                    $option2_num = $user_special_word_count['option2'] + $default_option_base[1];
                    $option3_num = $user_special_word_count['option3'] + $default_option_base[2];
                    $option4_num = $user_special_word_count['option4'] + $default_option_base[3];
                } else {
                    $option1_num = $user_special_word_count['option1'];
                    $option2_num = $user_special_word_count['option2'];
                    $option3_num = $user_special_word_count['option3'];
                    $option4_num = $user_special_word_count['option4'];
                }

                //判断选项个数
                $question_options       = SpecialWordModel::where('special_id', $data['special_id'])->where('id', $value['id'])->value('options');
                $question_options_count = count(json_decode($question_options));
                switch ($question_options_count) {
                    case '1':
                        $options = [$option1_num];
                        break;

                    case '2':
                        $options = [$option1_num, $option2_num];
                        break;

                    case '3':
                        $options = [$option1_num, $option2_num, $option3_num];
                        break;

                    case '4':
                        $options = [$option1_num, $option2_num, $option3_num, $option4_num];
                        break;
                }
                $special_word[$key]['options_num'] = $options;
                //获取值最多选项
                $max_arr = [$option1_num, $option2_num, $option3_num, $option4_num];
                $max_k   = 1;
                $max_v   = 0;
                foreach ($max_arr as $k => $v) {
                    if ($max_v <= $v) {
                        $max_v = $v;
                        $max_k = $k + 1;
                    }
                }
                $special_word[$key]['most_select'] = $max_k;

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
                //答对加金币
                $special_gold = SpecialGoldModel::where('special_id', $data['special_id'])->where('user_id', $data['user_id'])->find();
                if (!$special_gold) {
                    $timing_correct_gold = $config_data['timing_correct_gold'];
                    //保存获奖信息
                    $special_gold             = new SpecialGoldModel();
                    $special_gold->user_id    = $data['user_id'];
                    $special_gold->special_id = $data['special_id'];
                    $special_gold->gold       = $timing_correct_gold;
                    $special_gold->dday       = date('Ymd');
                    $special_gold->save();

                    $user_record       = UserRecordModel::where('user_id', $data['user_id'])->find();
                    $user_record->gold = $user_record->gold + $timing_correct_gold;
                    $user_record->save();
                }

                //生成兑换码
                $code = UserSpecialRedeemcodeModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->value('code');
                if (!isset($code)) {
                    $code                                = date('ymd', $display_time) . uniqid();
                    $user_special_redeemcode             = new UserSpecialRedeemcodeModel();
                    $user_special_redeemcode->user_id    = $data['user_id'];
                    $user_special_redeemcode->logo       = UserModel::where('id', $data['user_id'])->value('avatar');
                    $user_special_redeemcode->special_id = $data['special_id'];
                    $user_special_redeemcode->code       = $code;
                    $user_special_redeemcode->save();
                }

                //生成一条机器人数据
                $rebot_rand_arr = $config_data['rebot_rand_arr'];
                $rebot_id       = rand($rebot_rand_arr[0], $rebot_rand_arr[1]);
                $rebot_code     = UserSpecialRedeemcodeModel::where('user_id', $rebot_id)->where('special_id', $data['special_id'])->value('code');
                if (!isset($rebot_code)) {
                    $code1                               = date('ymd', $display_time) . uniqid();
                    $user_special_redeemcode             = new UserSpecialRedeemcodeModel();
                    $user_special_redeemcode->user_id    = $rebot_id;
                    $user_special_redeemcode->logo       = UserModel::where('id', $rebot_id)->value('avatar');
                    $user_special_redeemcode->special_id = $data['special_id'];
                    $user_special_redeemcode->code       = $code1;
                    $user_special_redeemcode->use_type   = 2;
                    $user_special_redeemcode->save();
                }

                //随机正确错误提示语
                //正确提示语
                $special_correct_arr = $config_data['special_correct_arr'];
                $c_k                 = array_rand($special_correct_arr);
                $correct_tip         = $special_correct_arr[$c_k];
                //错误提示语
                $special_error_arr = $config_data['special_error_arr'];
                $e_k               = array_rand($special_error_arr);
                $error_tip         = $special_error_arr[$e_k];

                return [
                    'remaining_time' => $remaining_time,
                    'is_end'         => 1,
                    'total_num'      => count($special_word),
                    'correct_num'    => $correct_num,
                    'correct_tip'    => $correct_tip,
                    'error_tip'      => $error_tip,
                    'code'           => $code,
                    'list'           => $special_word,
                ];
            } else {
                //随机正确错误提示语
                //正确提示语
                $special_correct_arr = $config_data['special_correct_arr'];
                $c_k                 = array_rand($special_correct_arr);
                $correct_tip         = $special_correct_arr[$c_k];
                //错误提示语
                $special_error_arr = $config_data['special_error_arr'];
                $e_k               = array_rand($special_error_arr);
                $error_tip         = $special_error_arr[$e_k];
                return [
                    'remaining_time' => $remaining_time,
                    'is_end'         => 1,
                    'total_num'      => count($special_word),
                    'correct_tip'    => $correct_tip,
                    'error_tip'      => $error_tip,
                    'correct_num'    => $correct_num,
                    'list'           => $special_word,
                ];
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 整点场抽奖页信息
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function prizePage($data)
    {
        try {
            //用户兑换码
            $user_code = UserSpecialRedeemcodeModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->value('code');
            $user_code = isset($user_code) ? $user_code : 0;
            //获奖列表
            $prize_list = UserSpecialRedeemcodeModel::where('special_id', $data['special_id'])->field('logo')->select();
            //结束时间

            $config_data       = $this->configData;
            $answer_time_limit = $config_data['answer_time_limit'];
            $special_over_time = $config_data['special_over_time'];
            $special_info      = SpecialModel::where('id', $data['special_id'])->find();
            $display_time      = $special_info['display_time'];
            $time_end          = $display_time + $answer_time_limit * 60 + $special_over_time * 60 - time();
            $time_end          = $time_end > 0 ? $time_end : 0;

            //奖品信息
            $special_prize = SpecialPrizeModel::where('id', $special_info['prize_id'])->field('name,img')->find();

            //场次信息
            $special_name = $special_info['title'];
            $special_time = date('Y-m-d H:i', $special_info['display_time']);
            return [
                'prize_info'     => $special_prize,
                'special_info'   => [
                    'special_name' => $special_name,
                    'special_time' => $special_time,
                ],
                'user_code'      => $user_code,
                'remaining_time' => $time_end,
                'prize_list'     => $prize_list,
            ];
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 整点场抽奖页抽奖
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function luckDraw($data)
    {
        try {
            $prize_info = UserSpecialPrizeModel::where('special_id', $data['special_id'])->field('code,user_id')->find();
            if (!$prize_info) {
                //获取后台抽奖方式
                $config_data     = $this->configData;
                $luck_draw_value = $config_data['luck_draw_value'];
                $list            = UserSpecialRedeemcodeModel::where('special_id', $data['special_id'])->select();
                $display_time    = SpecialModel::where('id', $data['special_id'])->value('display_time');
                switch ($luck_draw_value) {
                    //混合抽
                    case '0':
                        //获取中奖码列表
                        $lv = rand(1, 10000);
                        if ($lv == 2) {
                            $code_list = UserSpecialRedeemcodeModel::where('special_id', $data['special_id'])->where('use_type', 1)->column('code');
                            $k         = empty($code_list) ? -1 : array_rand($code_list);
                            $code      = $k == -1 ? 0 : $code_list[$k];
                        } else {
                            $code_list = UserSpecialRedeemcodeModel::where('special_id', $data['special_id'])->where('use_type', 2)->column('code');
                            $k         = empty($code_list) ? -1 : array_rand($code_list);
                            $code      = $k == -1 ? 0 : $code_list[$k];
                        }
                        break;
                    //从真人中抽
                    case '1':
                        //获取中奖码列表
                        $code_list = UserSpecialRedeemcodeModel::where('special_id', $data['special_id'])->where('use_type', 1)->column('code');

                        $k    = empty($code_list) ? -1 : array_rand($code_list);
                        $code = $k == -1 ? 0 : $code_list[$k];
                        break;
                    //从机器中抽
                    case '2':
                        //获取中奖码列表
                        $code_list = UserSpecialRedeemcodeModel::where('special_id', $data['special_id'])->where('use_type', 2)->column('code');
                        $k         = empty($code_list) ? -1 : array_rand($code_list);
                        $code      = $k == -1 ? 0 : $code_list[$k];
                        break;
                }
                //保存被抽中的信息
                // 开启事务
                Db::startTrans();
                try {
                    //保存中奖纪录
                    $user_special_prize = new UserSpecialPrizeModel();
                    foreach ($list as $key => $value) {
                        if ($code == $value['code']) {
                            $user_special_prize->user_id    = $value['user_id'];
                            $user_special_prize->special_id = $value['special_id'];
                            $user_special_prize->code       = $value['code'];
                            $user_special_prize->use_type   = $value['use_type'];
                            $user_special_prize->prize_id   = SpecialModel::where('id', $data['special_id'])->value('prize_id');
                            $user_special_prize->save();

                            $prize_user_id = $value['user_id'];
                            break;
                        } else {
                            continue;
                        }
                    }

                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
            } else {
                $code          = $prize_info['code'];
                $prize_user_id = $prize_info['user_id'];
            }
            //获取中奖用户信息
            if (isset($prize_user_id)) {
                $prize_user_info = UserModel::where('id', $prize_user_id)->field('id,nickname')->find();
            } else {
                $prize_user_info = [];
            }

            return [
                'prize_user_info' => $prize_user_info,
            ];
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 使用兑换码兑奖
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function cashPrize($data)
    {
        try {
            //判断用户是否拥有该兑换码
            $user_code_info = UserSpecialRedeemcodeModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('use_type', 1)->find();
            if (!$user_code_info) {
                return [
                    'status' => 0,
                    'msg'    => '兑换码错误',
                ];
            }
            //判断用户兑奖码是否中奖
            $user_prize_info = UserSpecialPrizeModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('use_type', 1)->find();
            if (!$user_prize_info) {
                return [
                    'status' => 0,
                    'msg'    => '该兑换码未中奖',
                ];
            }
            //判断是否已使用
            if ($user_prize_info['is_use'] == 1) {
                return [
                    'status' => 0,
                    'msg'    => '该兑换码已使用',
                ];
            }

            //使用兑换码
            // 开启事务
            Db::startTrans();
            try {
                $user_prize_info->is_use = 1;
                $user_prize_info->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];

        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取用户获奖纪录
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function userPrize($userId)
    {
        try {
            $prize_list = UserSpecialPrizeModel::where('user_id', $userId)->select();

            if (count($prize_list) > 0) {
                foreach ($prize_list as $key => $value) {
                    $prize_info                     = SpecialPrizeModel::get($value['prize_id']);
                    $prize_list[$key]['prize_name'] = $prize_info['name'];
                    $prize_list[$key]['prize_img']  = $prize_info['img'];

                    //判断是否有默认地址
                    $openid       = UserModel::where('id', $value['user_id'])->value('openid');
                    $address_info = AddressModel::where('openid', $openid)->where('status', 1)->find();
                    if ($address_info) {
                        $prize_list[$key]['is_deal'] = 1;
                    } else {
                        $prize_list[$key]['is_deal'] = 0;
                    }
                }

            } else {
                $prize_list = [];
            }

            return [
                'prize_list' => $prize_list,
            ];
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取用户整点场纪录
     * @param  array $userId 用户id
     * @return [type]       [description]
     */
    public function userSpecialRecord($userId)
    {
        try {
            if (isset($data['is_week']) && $data['is_week'] == 1) {
                //周纪录
                $start        = strtotime(date('Y-m-d 00:00:00', strtotime('-1 week')));
                $end          = strtotime(date('Y-m-d 23:59:59'));
                $user_special = UserSpecialModel::where('user_id', $userId)->where('create_time', 'between', [$start, $end])->order('create_time desc')->select();
            } else {
                //个人纪录
                $user_special = UserSpecialModel::where('user_id', $userId)->order('create_time desc')->select();
            }
            $config_data       = $this->configData;
            $answer_time_limit = $config_data['answer_time_limit'];
            //过期时长
            $special_over_time = $config_data['special_over_time'];
            foreach ($user_special as $key => $value) {
                $special_info                        = SpecialModel::where('id', $value['special_id'])->field('title,display_time')->find();
                $display_time                        = $special_info['display_time'];
                $user_special[$key]['start_time']    = date('H:i', $display_time);
                $special_title                       = $special_info['title'];
                $user_special[$key]['special_title'] = $special_title;
                $end_time                            = $display_time + $answer_time_limit * 60;
                $over_time                           = $end_time + $special_over_time * 60;
                if ($end_time > time()) {
                    $user_special[$key]['is_end'] = 0;
                } else {
                    $user_special[$key]['is_end'] = 1;
                }
                if ($over_time > time()) {
                    $user_special[$key]['is_over'] = 0;
                } else {
                    $user_special[$key]['is_over'] = 1;
                }
                $user_special_word = UserSpecialWordModel::where('user_id', $value['user_id'])->where('special_id', $value['special_id'])->select();
                $is_correct        = 0;
                foreach ($user_special_word as $k => $v) {
                    $most_select = UserSpecialWordCountModel::where('special_id', $v['special_id'])->where('special_word_id', $v['special_word_id'])->value('most_select');
                    if ($v['user_select'] == $most_select) {
                        $is_correct = 1;
                    } else {
                        $is_correct = 0;
                    }
                }
                if ($is_correct == 1) {
                    $user_special[$key]['is_correct'] = 1;
                } else {
                    $user_special[$key]['is_correct'] = 0;
                }
            }
            return $user_special;
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 重新答题
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function reAswer($data)
    {
        try {
            if ($data['type'] == 1) {
                //消耗金币重新答题
                //重答需消耗金币
                $config_data           = $this->configData;
                $reanswer_consume_gold = $config_data['reanswer_consume_gold'];
                $user_record           = UserRecordModel::where('user_id', $data['user_id'])->find();
                if ($user_record->gold >= $reanswer_consume_gold) {
                    // 开启事务
                    Db::startTrans();
                    try {
                        $user_record->gold = $user_record->gold - $reanswer_consume_gold;
                        $user_record->save();
                        Db::commit();
                        return [
                            'status' => 1,
                            'msg'    => 'ok',
                            'data'   => '',
                        ];

                    } catch (\Exception $e) {
                        Db::rollback();
                        return [
                            'status' => 0,
                            'msg'    => 'fail',
                            'data'   => '',
                        ];
                    }
                } else {
                    return [
                        'status' => 0,
                        'msg'    => '金币不够',
                        'data'   => '',
                    ];
                }
            } else if ($data['type'] == 2) {
                //消耗返回卡重新答题
                $openid      = UserModel::where('id', $data['user_id'])->value('openid');
                $dday        = date('ymd');
                $regret_card = RegretCardModel::where('openid', $openid)->where('add_date', $dday)->find();
                $times       = isset($regret_card['times']) ? $regret_card['times'] : 0;
                if ($times <= 0) {
                    return [
                        'status' => 0,
                        'msg'    => '返回卡不够',
                        'data'   => '',
                    ];
                }
                // 开启事务
                Db::startTrans();
                try {
                    $regret_card->times = $regret_card->times - 1;
                    $regret_card->save();
                    Db::commit();
                    return [
                        'status' => 1,
                        'msg'    => 'ok',
                        'data'   => '',
                    ];

                } catch (\Exception $e) {
                    Db::rollback();
                    return [
                        'status' => 0,
                        'msg'    => 'fail',
                        'data'   => '',
                    ];
                }
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 随机生成当天整点场
     * @param
     * @return [type]       [description]
     */
    public function randGetSpecial()
    {
        try {
            $start        = strtotime(date('Y-m-d 00:00:00'));
            $end          = strtotime(date('Y-m-d 23:59:59'));
            $special_list = SpecialModel::where('display_time', 'between', [$start, $end])->select();
            //整点场次时间配置
            $config_data       = $this->configData;
            $special_times_arr = $config_data['special_times_arr'];
            if (count($special_list) > 0) {
                //已存在
                $special_count = count($special_list);
                if ($special_count >= count($special_times_arr)) {
                    return [
                        'status' => 1,
                        'msg'    => 'ok',
                        'data'   => '',
                    ];
                } else {
                    $times_arr = [];
                    foreach ($special_list as $key => $value) {
                        $times_arr[] = date('H', $value['display_time']);
                    }
                    $times_arr_diff = array_diff($special_times_arr, $times_arr);
                    if (empty($times_arr_diff)) {
                        return [
                            'status' => 1,
                            'msg'    => 'ok',
                            'data'   => '',
                        ];
                    } else {
                        $need_times_arr = [];
                        foreach ($times_arr_diff as $key => $value) {
                            $need_times_arr[] = $value;
                        }

                        $special_house = SpecialWarehouseModel::select();
                        $special_arr   = [];
                        foreach ($special_house as $key => $value) {
                            $special_arr[] = $key;
                        }
                        $special_rand_arr = array_rand($special_arr, count($need_times_arr));
                        $special          = [];
                        if (is_array($special_rand_arr)) {
                            foreach ($special_rand_arr as $key => $value) {
                                $special[] = $special_house[$value];
                            }
                        } else {
                            $special[] = $special_house[$special_rand_arr];
                        }

                        $today = date('Y-m-d');

                        // 开启事务
                        Db::startTrans();
                        try {
                            //保存场次
                            foreach ($special as $key => $value) {
                                $special_obj = new SpecialModel();
                                // $special_obj->title        = $value['title'];
                                $special_obj->title        = date('Ymd ' . $need_times_arr[$key] . ':00') . '场';
                                $special_obj->des          = $value['des'];
                                $special_obj->img          = $value['img'];
                                $special_obj->banners      = $value['banners'];
                                $special_obj->prize_id     = $value['prize_id'];
                                $special_obj->display_time = strtotime(date('Y-m-d ' . $need_times_arr[$key] . ':00:00'));
                                $special_obj->create_time  = time();

                                $special_obj->save();

                                //保存题目
                                $special_warehouse_id   = $value['id'];
                                $special_word_warehouse = SpecialWordWarehouseModel::where('special_warehouse_id', $special_warehouse_id)->select();
                                if (count($special_word_warehouse) > 0) {
                                    $special_question_num = $config_data['special_question_num'];
                                    $arr                  = [];
                                    foreach ($special_word_warehouse as $key => $value) {
                                        $arr[] = $value['id'];
                                    }
                                    $rand_keys        = array_rand($arr, $special_question_num);
                                    $special_word_arr = [];
                                    if (is_array($rand_keys)) {
                                        foreach ($rand_keys as $key => $value) {
                                            $special_word_arr[] = $special_word_warehouse[$value];
                                        }
                                    } else {
                                        $special_word_arr[] = $special_word_warehouse[$rand_keys];
                                    }
                                    if (count($special_word_arr) > 0) {
                                        foreach ($special_word_arr as $k => $v) {
                                            $special_word_obj              = new SpecialWordModel();
                                            $special_word_obj->special_id  = $special_obj->id;
                                            $special_word_obj->title       = $v['title'];
                                            $special_word_obj->des         = $v['des'];
                                            $special_word_obj->options     = $v['options'];
                                            $special_word_obj->create_time = time();
                                            $special_word_obj->save();
                                        }
                                    }

                                }
                            }
                            Db::commit();
                            return [
                                'status' => 1,
                                'msg'    => 'ok',
                                'data'   => '',
                            ];
                        } catch (\Exception $e) {
                            Db::rollback();
                            return [
                                'status' => 0,
                                'msg'    => 'fail',
                                'data'   => '',
                            ];
                        }
                    }
                }
            } else {
                //未存在
                $special_house = SpecialWarehouseModel::select();
                $special_arr   = [];
                foreach ($special_house as $key => $value) {
                    $special_arr[] = $key;
                }
                $special_rand_arr = array_rand($special_arr, count($special_times_arr));

                $special = [];
                foreach ($special_rand_arr as $key => $value) {
                    $special[] = $special_house[$value];
                }
                $today = date('Y-m-d');
                // 开启事务
                Db::startTrans();
                try {
                    //保存场次
                    foreach ($special as $key => $value) {
                        $special_obj = new SpecialModel();
                        // $special_obj->title        = $value['title'];
                        $special_obj->title        = date('Ymd ' . $special_times_arr[$key] . ':00') . '场';
                        $special_obj->des          = $value['des'];
                        $special_obj->img          = $value['img'];
                        $special_obj->banners      = $value['banners'];
                        $special_obj->prize_id     = $value['prize_id'];
                        $special_obj->display_time = strtotime(date('Y-m-d ' . $special_times_arr[$key] . ':00:00'));
                        $special_obj->create_time  = time();

                        $special_obj->save();

                        //保存题目
                        $special_warehouse_id   = $value['id'];
                        $special_word_warehouse = SpecialWordWarehouseModel::where('special_warehouse_id', $special_warehouse_id)->select();
                        if (count($special_word_warehouse) > 0) {
                            $special_question_num = $config_data['special_question_num'];
                            $arr                  = [];
                            foreach ($special_word_warehouse as $key => $value) {
                                $arr[] = $value['id'];
                            }
                            $rand_keys        = array_rand($arr, $special_question_num);
                            $special_word_arr = [];
                            if (is_array($rand_keys)) {
                                foreach ($rand_keys as $key => $value) {
                                    $special_word_arr[] = $special_word_warehouse[$value];
                                }
                            } else {
                                $special_word_arr[] = $special_word_warehouse[$rand_keys];
                            }
                            if (count($special_word_arr) > 0) {
                                foreach ($special_word_arr as $k => $v) {
                                    $special_word_obj              = new SpecialWordModel();
                                    $special_word_obj->special_id  = $special_obj->id;
                                    $special_word_obj->title       = $v['title'];
                                    $special_word_obj->des         = $v['des'];
                                    $special_word_obj->options     = $v['options'];
                                    $special_word_obj->create_time = time();
                                    $special_word_obj->save();
                                }
                            }

                        }
                    }
                    Db::commit();
                    return [
                        'status' => 1,
                        'msg'    => 'ok',
                        'data'   => '',
                    ];
                } catch (\Exception $e) {
                    Db::rollback();
                    return [
                        'status' => 0,
                        'msg'    => 'fail',
                        'data'   => '',
                    ];
                }
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    // /**
    //  * 保存模板消息参数
    //  * @param  array $data 接收参数
    //  * @return [type]       [description]
    //  */
    // public function saveTemplateInfo($data)
    // {
    //     try {
    //         // 开启事务
    //         Db::startTrans();
    //         try {
    //             $template_info = TemplateInfoModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('special_word_id', $data['special_word_id'])->where('dday', date('Ymd'))->find();
    //             $start = strpos($data['page'],"?");
    //             $data['page'] = substr($data['page'],0,$start);
    //             if ($template_info) {
    //                 $template_info->page    = $data['page'];
    //                 $template_info->form_id = $data['form_id'];
    //                 $template_info->save();
    //             } else {
    //                 $template_info                  = new TemplateInfoModel();
    //                 $template_info->user_id         = $data['user_id'];
    //                 $template_info->special_id      = $data['special_id'];
    //                 $template_info->special_word_id = $data['special_word_id'];
    //                 $template_info->page            = $data['page'];
    //                 $template_info->form_id         = $data['form_id'];
    //                 $template_info->dday            = date('Ymd');
    //                 $template_info->save();
    //             }
    //             Db::commit();
    //             return [
    //                 'status' => 1,
    //                 'msg'    => 'ok',
    //             ];
    //         } catch (\Exception $e) {
    //             Db::rollback();
    //             return [
    //                 'status' => 0,
    //                 'msg'    => 'fail',
    //             ];
    //         }
    //     } catch (Exception $e) {
    //         lg($e);
    //         throw new \Exception("系统繁忙");
    //     }
    // }

    /**
     * 保存模板消息参数至redis
     * @param  array $data 接收参数
     * @return [type]       [description]
     */
    public function saveTemplateInfo($data)
    {
        try {
            // 开启事务
            Db::startTrans();
            try {
                //保存参数信息
                $template_info = TemplateInfoModel::where('user_id', $data['user_id'])->where('special_id', $data['special_id'])->where('special_word_id', $data['special_word_id'])->where('dday', date('Ymd'))->find();
                $start         = strpos($data['page'], "?");
                $data['page']  = substr($data['page'], 0, $start);
                if ($template_info) {
                    $template_info->page    = $data['page'];
                    $template_info->form_id = $data['form_id'];
                    $template_info->save();
                } else {
                    $template_info                  = new TemplateInfoModel();
                    $template_info->user_id         = $data['user_id'];
                    $template_info->special_id      = $data['special_id'];
                    $template_info->special_word_id = $data['special_word_id'];
                    $template_info->page            = $data['page'];
                    $template_info->form_id         = $data['form_id'];
                    $template_info->dday            = date('Ymd');
                    $template_info->save();
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }

            // $save_data                    = [];
            $special_info = SpecialModel::where('id', $data['special_id'])->find();
            // $save_data['special_word_id'] = $data['special_word_id'];
            // $save_data['special_id']      = $data['special_id'];
            // $save_data['user_id']         = $data['user_id'];
            // $save_data['page']            = $data['page'];
            // $save_data['form_id']         = $data['form_id'];
            // $save_data['display_time']    = $special_info['display_time'];
            //答题时长
            $config_data = $this->configData;
            // $save_data['answer_time_limit'] = $config_data['answer_time_limit'];

            $save_data = array(
                'special_word_id'   => $data['special_word_id'],
                'special_id'        => $data['special_id'],
                'user_id'           => $data['user_id'],
                'page'              => $data['page'],
                'form_id'           => $data['form_id'],
                'display_time'      => $special_info['display_time'],
                'answer_time_limit' => $config_data['answer_time_limit'],
            );

            //初始化
            $redis = new Redis(Config::get('redis_config'));
            //模板消息key值
            $template_info_key = Config::get('template_info_key');
            $template_list     = [];
            $template_list[]   = $save_data;
            // dump($template_list);exit;
            $redis->set($template_info_key, $template_list);
            // $redis->set($template_info_key, null);
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 清空redis
     * @return [type]       [description]
     */
    public function clearRedis($data)
    {
        try {
            //初始化
            $redis = new Redis(Config::get('redis_config'));
            //模板消息key值
            $template_info_key = Config::get('template_info_key');
            if (isset($data['test']) && $data['test'] == 1) {
                dump($redis->get($template_info_key));
            } else {
                $redis->set($template_info_key, null);
            }
        } catch (Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

}
