<?php

namespace app\bxdj\controller\api\v1_0_1;

use think\facade\Request;

use think\Db;

use think\facade\Config;
use think\facade\Cache;
/**
 * 消息控制器类
 */
class Message
{
	/**
	 * 查询消息信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/message/index.html&openid=1;
		require_params('openid');
		$openid = Request::param('openid');

		//判断消息是否已经被点击过
		$is_clicked = Db::name('message_click')->field('message_id')->where('openid',$openid)->select();

		$result = Cache::get(config('message_info'));

		if(empty($is_clicked)){
				foreach ($result as $k1 => $v1) {
					$result[$k1]['status'] = 0;
				}
		}else{
			foreach ($result as $k2 => $v2) {
				foreach ($is_clicked as $k3 => $v3) {
					 if ($v2['id'] == $v3['message_id']) {
					 	 $result[$k2]['status'] = 1;
					 }
				}
				if(!isset($result[$k2]['status'])){
					$result[$k2]['status'] = 0;
				}
			}
		}

		foreach ($result as $key => $value) {
			$result[$key]['create_time'] = date('Y-m-d',$value['create_time']);
		}
		
		return result(200, '0k', $result);
		
	}

	/**
	 * 判断消息是否已经被信息
	 * @return boolen
	 */
	public function message_click()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/message/message_click.html?openid=1&id=2;
		require_params('openid','id'); //id为消息对应的ID
		$data = Request::param();

		$is_exsit = Db::name('message')->where('id',$data['id'])->find();

		if(!$is_exsit){
			return result(200, '无该消息');
		}
		
		$res = Db::name('message_click')->where(['message_id'=>$data['id'],'openid'=>$data['openid']])->find();
		
		if(!empty($res)){
			return result(200, '您已经查看过该消息');
		}


		$insert_data = [

			'message_id' => $data['id'],
			'openid' => $data['openid'],
			'click_time' => time()
		];


		$result = Db::name('message_click')->insert($insert_data);

		return result(200, '0k', $result);
	}

	/**
	 * 返回具体消息的接口
	 * @return boolen
	 */
	public function message_detail()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/message/message_detail.html?id=2;
		require_params('id'); //id为消息对应的ID
		$data = Request::param();

		$is_exsit = Db::name('message')->where('id',$data['id'])->find();

		if(!$is_exsit){
			return result(200, '无该消息');
		}
		return result(200, '0k', $is_exsit);
	}

	
}