<?php

namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\TemplateInfo as TemplateInfoModel;
use app\qmxz\model\TemplateRecord as TemplateRecordModel;
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
        //答题时长
        $config_data       = $this->configData;
        $answer_time_limit = $config_data['answer_time_limit'];
        $start             = strtotime(date('Y-m-d 00:00:00'));
        $end               = strtotime(date('Y-m-d 23:59:59'));
        $special           = SpecialModel::where('display_time', 'between', [$start, $end])->select();
        foreach ($special as $key => $value) {
            $end_time = $value['display_time'] + $answer_time_limit * 60;
            if ($end_time > time()) {
                continue;
            } else {
                //获取场次参与人数id
                $template_info = TemplateInfoModel::where('special_id', $value['id'])->where('dday', date('Ymd'))->select();
                if (empty($template_info)) {
                    continue;
                } else {
                    foreach ($template_info as $k => $v) {
                        //判断是否已发送过模板消息
                        $template_record = TemplateRecordModel::where('user_id', $v['user_id'])->where('special_id', $value['id'])->where('dday', date('Ymd'))->find();
                        if ($template_record) {
                            continue;
                        } else {
                            //发送模板消息
                            $send_url = Config::get('send_url');
                            $data     = json_decode(file_get_contents(sprintf($send_url, $v['special_word_id'], $v['user_id'], $v['page'], $v['form_id'], $v['special_id'])), true);
                        }
                    }
                }
            }
        }

    }
}
