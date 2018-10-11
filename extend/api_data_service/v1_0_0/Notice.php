<?php

namespace api_data_service\v1_0_0;

use think\Db;
use think\facade\Cache;
use think\facade\Config;
//use api_data_service\Config as ConfigService;
//use model\CardContent;

use model\User as UserModel;

//use model\User;
//use model\Cate;
//use model\JointBlessings;
use app\bxdj\model\SendLog;



/**
 * 模板小心服务类
 */
class Notice
{
	/**
	 * 发送模板消息
	 * @param  $data 请求数据
	 * @return array
	 */
	public function sendTemplateMsg($data)
	{

		try {
			//获取access_token
			
			if(Cache::has('access_token')){
				$access_token = Cache::get('access_token');
			}else{
				$appid = Config::get('wx_appid');
        		$secret = Config::get('wx_secret');
				$get_access_url = Config::get('get_access_url');
				$access_token_data = json_decode(file_get_contents(sprintf($get_access_url, $appid, $secret)), true);
				$access_token = $access_token_data['access_token'];
				$access_expires_in = $access_token_data['expires_in'] - 10;
				Cache::set('access_token', $access_token, $access_expires_in);
			}

			//抓取https数据url
			$send_template_url = sprintf(Config::get('send_template_url'), $access_token);

			//模板ID：9 为签到模板  10 为团队赛开始时间模板
			$tp_id = $data['type_id'];
			$template_info = Db::name('template_msg')->where('id', $tp_id)->where('status', 1)->find();
			$template_id = $template_info['template_id'];
			//dump($template_info);
			
			$content = unserialize($template_info['content']);
			$time = date("Y-m-d H:i:s");
			
			$countModel = Db::name('register')->field('count_days')->where('openid', $data['openid'])->order('create_time desc')->find();
			$count = $countModel['count_days'] . '天' ;
			// dump($content);exit;
			foreach ($content as $key => $value) {
				
				if(strpos($value['value'],'{time}') !== false){ 
				 	$content[$key]['value'] = str_replace('{time}', $time, $content[$key]['value']);
				}
				if(strpos($value['value'],'{count}') !== false){ 
				 	$content[$key]['value'] = str_replace('{count}', $count, $content[$key]['value']);
				}
			}
		
			$postData = [
			  "touser" => $data['openid'],
			  "template_id" => $template_id,
			  "page" => $data['page'],
			  "form_id" => $data['form_id'],
			  "data" => $content,
			  "emphasis_keyword" => ""
			];
			
			$postData = json_encode($postData);
			 //dump($postData);exit;
			
			// $send_template_data = https_request($send_template_url, $postData);

			$send_template_data = json_decode(sendCmd($send_template_url, $postData));
			//更新服务消息记录表
			$sendLog = new SendLog();
			$sendLog->touser = $data['openid'];
			$sendLog->template_id = $template_id;
			$sendLog->page = $data['page'];
			$sendLog->form_id = $data['form_id'];
			$sendLog->content = $template_info['content'];
			$sendLog->errcode = $send_template_data->errcode;
			$sendLog->errmsg = $send_template_data->errmsg;
			$sendLog->save();
			// dump($postData);exit;

			//更新用户预约的信息表
			Db::name('appoint_message')->where('id',$data['id'])->update(['status'=>1]);

			return $send_template_data;

		} catch (\Exception $e) {
			return 'fail';
            //throw new \Exception("系统繁忙");
        }
	}

	/**
	 * 获取模板小列表
	 * 
	 */
	function getTemplateList(){
		//获取access_token
		if(Cache::has('access_token')){
			$access_token = Cache::get('access_token');
		}else{
			$appid = Config::get('wx_appid');
    		$secret = Config::get('wx_secret');
			$get_access_url = Config::get('get_access_url');
			$access_token_data = json_decode(file_get_contents(sprintf($get_access_url, $appid, $secret)), true);
			$access_token = $access_token_data['access_token'];
			$access_expires_in = $access_token_data['expires_in'] - 10;
			Cache::set('access_token', $access_token, $access_expires_in);
		}
		$url = Config::get('wx_get_template_list');
		$postData = [
			'access_token' => $access_token,
			"offset" => 0,
			"count" => 10
		];
		$data = https_request($url, $postData);
		dump($data);exit;
	}



}