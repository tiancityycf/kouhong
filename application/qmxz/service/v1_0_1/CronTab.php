<?php
namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\TemplateInfo as TemplateInfoModel;
use app\qmxz\model\TemplateRecord as TemplateRecordModel;
use think\cache\driver\Redis;
use think\Db;
use think\facade\Config;

/**
 * 脚本服务类
 */
class CronTab
{
    protected $configData;

    public function __construct($configData)
    {
        $this->configData = $configData;
    }

    public function sendNotice()
    {
        set_time_limit(0);
        //答题时长
        $config_data       = $this->configData;
        $answer_time_limit = $config_data['answer_time_limit'];
        $start             = strtotime(date('Y-m-d 00:00:00'));
        $end               = strtotime(date('Y-m-d 23:59:59'));
        //发送模板消息
        $send_url = Config::get('send_url');
        //访问结果页
        $special_result_url = Config::get('special_result_url');
        while (true) {
            if (time() >= strtotime(date('Y-m-d 23:00:00'))) {
                break;
            }
            $special = SpecialModel::where('display_time', 'between', [$start, $end])->select();
            foreach ($special as $key => $value) {
                $end_time = $value['display_time'] + $answer_time_limit * 60;
                if ($end_time <= time()) {
                    //获取场次参与人数id
                    $template_list = TemplateInfoModel::where('special_id', $value['id'])->where('dday', date('Ymd'))->where('status', 0)->select();
                    if (!empty($template_list)) {
                        foreach ($template_list as $k => $v) {
                            if ($k % 100 == 0) {
                                sleep(1);
                            }
                            //判断是否已发送过模板消息
                            $template_record = TemplateRecordModel::where('user_id', $v['user_id'])->where('special_id', $value['id'])->where('dday', date('Ymd'))->find();
                            if (!$template_record) {
                                try {
                                    $data = json_decode(https_get(sprintf($send_url, $v['special_word_id'], $v['user_id'], $v['page'], $v['form_id'], $v['special_id'])));

                                    // 开启事务
                                    Db::startTrans();
                                    try {
                                        if ($data['data']['errcode'] == 0) {
                                            //修改发送状态
                                            $template_info         = TemplateInfoModel::where('special_id', $value['id'])->where('user_id', $v['user_id'])->where('dday', date('Ymd'))->where('special_word_id', $v['special_word_id'])->where('status', 0)->find();
                                            $template_info->status = 1;
                                            $template_info->save();
                                        }

                                        //保存发送记录
                                        $template_record             = new TemplateRecordModel();
                                        $template_record->user_id    = $v['user_id'];
                                        $template_record->special_id = $v['special_id'];
                                        $template_record->dday       = date('Ymd');
                                        $template_record->save();

                                        $result_data = json_decode(https_get(sprintf($special_result_url, $v['user_id'], $v['special_id'])));
                                        Db::commit();
                                    } catch (\Exception $e) {
                                        lg($e);
                                        Db::rollback();
                                    }
                                } catch (Exception $e) {
                                    lg($e);
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 从缓存中发送模板消息
     * @return [type] [description]
     */
    public function redisSendNotice()
    {
        set_time_limit(0);
        //模板消息key值
        $template_info_key = Config::get('template_info_key');
        //初始化
        $redis = new Redis(Config::get('redis_config'));
        //发送模板消息
        $send_url = Config::get('send_url');
        //访问结果页url
        $special_result_url = Config::get('special_result_url');
        while (true) {
            trace("xxx",'error');
            if ($redis->has($template_info_key)) {
                if (time() >= strtotime(date('Y-m-d 23:00:00'))) {
                    $redis->rm($template_info_key);
                    break;
                }
                //获取模板消息队列
                $template_list = $redis->get($template_info_key);
                if (!empty($template_list)) {
                    trace(json_encode($template_list),'error');
                    foreach ($template_list as $k => $v) {
                        if ($k % 100 == 0) {
                            sleep(1);
                        }
                        $end_time = $v['display_time'] + $v['answer_time_limit'] * 60;
                        if ($end_time <= time()) {
                            try {
                                if ($v['form_id'] == '') {
                                    //删除记录
                                    unset($template_list[$k]);
                                    //修改缓存信息
                                    if(empty($template_list)){
                                        $redis->rm($template_info_key);
                                    }else{
                                        $redis->set($template_info_key, $template_list);
                                    }
                                    break;
                                } else {
                                    $data = json_decode(https_get(sprintf($send_url, $v['special_word_id'], $v['user_id'], $v['page'], $v['form_id'], $v['special_id'])));
                                }
                            } catch (Exception $e) {
                                // 开启事务
                                Db::startTrans();
                                try {
                                    //保存发送记录
                                    $template_record = TemplateRecordModel::where('user_id', $v['user_id'])->where('special_id', $v['special_id'])->find();
                                    if (!$template_record) {
                                        $template_record             = new TemplateRecordModel();
                                        $template_record->user_id    = $v['user_id'];
                                        $template_record->special_id = $v['special_id'];
                                        $template_record->dday       = date('Ymd');
                                        $template_record->save();
                                    }
                                    Db::commit();
                                } catch (\Exception $e) {
                                    Db::rollback();
                                    try {
                                        $result_data = json_decode(https_get(sprintf($special_result_url, $v['user_id'], $v['special_id'])));
                                    } catch (Exception $e) {
                                        //删除记录
                                        unset($template_list[$k]);
                                        //修改缓存信息
                                        $redis->set($template_info_key, $template_list);
                                        lg($e);
                                        continue;

                                    }
                                }

                            }
                        }
                    }
                } else {
                    $redis->rm($template_info_key);
                }
            } else {
                continue;
            }
        }
    }
}
