<?php

namespace app\qmxz\service\v1_0_1;

use app\qmxz\model\SendLog;
use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\SpecialWord as SpecialWordModel;
use app\qmxz\model\TemplateMsg as TemplateMsgModel;
use app\qmxz\model\User;
use app\qmxz\model\UserSpecialWordCount as UserSpecialWordCountModel;
use think\facade\Cache;
use think\facade\Config;

/**
 * 模板小心服务类
 */
class Notice
{
    protected $configData;

    public function __construct($configData)
    {
        $this->configData = $configData;
    }

    /**
     * 发送模板消息
     * @param  $data 请求数据
     * @return array
     */
    public function sendTemplateMsg($data)
    {
        try {
            //获取access_token
            if (Cache::has('access_token')) {
                $access_token = Cache::get('access_token');
            } else {
                $appid             = Config::get('wx_appid');
                $secret            = Config::get('wx_secret');
                $get_access_url    = Config::get('get_access_url');
                $access_token_data = json_decode(file_get_contents(sprintf($get_access_url, $appid, $secret)), true);
                $access_token      = $access_token_data['access_token'];
                $access_expires_in = $access_token_data['expires_in'] - 10;
                Cache::set('access_token', $access_token, $access_expires_in);
            }

            //抓取https数据url
            $send_template_url = sprintf(Config::get('send_template_url'), $access_token);
            //模板ID
            $config_data   = $this->configData;
            $tp_id         = $config_data['wx_template_id'];
            $template_info = TemplateMsgModel::where('id', $tp_id)->where('status', 1)->find();
            $template_id   = $template_info['template_id'];
            if (isset($data['name']) && ($data['name'] != '')) {
                $name = $data['name'];
            } else {
                if (isset($data['user_id']) && ($data['user_id'] != '')) {
                    $name = User::where('id', $data['user_id'])->value('nickname');
                } else {
                    $name = '匿名';
                }
            }
            $content = unserialize($template_info['content']);
            $time    = date("Y-m-d H:i:s");
            
            $special_word_id = SpecialWordModel::where('special_id', $data['special_id'])->value('id');
            //答对人数
            $user_special_word_count = UserSpecialWordCountModel::where('special_id', $data['special_id'])->where('special_word_id', $special_word_id)->find();
            $num1                    = isset($user_special_word_count['option1']) ? $user_special_word_count['option1'] : 0;
            $num2                    = isset($user_special_word_count['option2']) ? $user_special_word_count['option2'] : 0;
            $num3                    = isset($user_special_word_count['option3']) ? $user_special_word_count['option3'] : 0;
            $num4                    = isset($user_special_word_count['option4']) ? $user_special_word_count['option4'] : 0;
            $num_arr                 = [$num1, $num2, $num3, $num4];
            $most_k                  = isset($user_special_word_count['most_select']) ? $user_special_word_count['most_select'] : 1;
            $num                     = $num_arr[$most_k - 1];
            //题目名称
            $question = SpecialWordModel::where('special_id', $data['special_id'])->where('id', $data['id'])->value('title');
            //场次
            $special = SpecialModel::where('id', $data['special_id'])->value('title');
            foreach ($content as $key => $value) {
                if (strpos($value['value'], '{time}') !== false) {
                    $content[$key]['value'] = str_replace('{time}', $time, $content[$key]['value']);
                }
                if (strpos($value['value'], '{name}') !== false) {
                    $content[$key]['value'] = str_replace('{name}', $name, $content[$key]['value']);
                }
                if (strpos($value['value'], '{num}') !== false) {
                    $content[$key]['value'] = str_replace('{num}', $num, $content[$key]['value']);
                }
                if (strpos($value['value'], '{question}') !== false) {
                    $content[$key]['value'] = str_replace('{question}', $question, $content[$key]['value']);
                }
                if (strpos($value['value'], '{special}') !== false) {
                    $content[$key]['value'] = str_replace('{special}', $special, $content[$key]['value']);
                }
            }
            $openid   = User::where('id', $data['user_id'])->value('openid');
            $start = strpos($data['page'],"/");
            if($start === 0){
                $data['page'] = ltrim($data['page'], '/');
            }
            $data['page'] = $data['page'].'?special_id='.$data['special_id'];
            $postData = [
                "touser"           => $openid,
                "template_id"      => $template_id,
                "page"             => $data['page'],
                "form_id"          => $data['form_id'],
                "data"             => $content,
                "emphasis_keyword" => "",
            ];
            $postData             = json_encode($postData);
            $send_template_data   = json_decode(sendCmd($send_template_url, $postData));
            if($send_template_data->errcode > 0){
                $sendLog              = new SendLog();
                $sendLog->touser      = $openid;
                $sendLog->template_id = $template_id;
                $sendLog->page        = $data['page'];
                $sendLog->form_id     = $data['form_id'];
                $sendLog->content     = $template_info['content'];
                $sendLog->errcode     = $send_template_data->errcode;
                $sendLog->errmsg      = $send_template_data->errmsg;
                $sendLog->save();
            }
            return $send_template_data;
        } catch (\Exception $e) {
            lg($e);
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 获取模板小列表
     *
     */
    public function getTemplateList()
    {
        //获取access_token
        if (Cache::has('access_token')) {
            $access_token = Cache::get('access_token');
        } else {
            $appid             = Config::get('wx_appid');
            $secret            = Config::get('wx_secret');
            $get_access_url    = Config::get('get_access_url');
            $access_token_data = json_decode(file_get_contents(sprintf($get_access_url, $appid, $secret)), true);
            $access_token      = $access_token_data['access_token'];
            $access_expires_in = $access_token_data['expires_in'] - 10;
            Cache::set('access_token', $access_token, $access_expires_in);
        }
        $url      = Config::get('wx_get_template_list');
        $postData = [
            'access_token' => $access_token,
            "offset"       => 0,
            "count"        => 10,
        ];
        $data = https_request($url, $postData);
        dump($data);exit;
    }
}
