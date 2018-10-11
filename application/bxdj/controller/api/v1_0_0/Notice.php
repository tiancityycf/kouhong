<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;
use think\Db;
use api_data_service\v1_0_0\Notice as NoticeModel;

use app\bxdj\model\AppointMessage;

/**
 * 服务通知控制器类
 */
class Notice 
{
	
	/**
	 * 预约发送服务消息接口
	 * return boolen
	 */
	public function appointMessage()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Notice/appointMessage.html?openid=1&type_id=9&page=index&form_id=2&send_time=2018-09-28 10:00:00;
        require_params('openid', 'type_id', 'page', 'form_id','send_time');  //type_id为使用的后台模板id
        $data = Request::param();
        $data['create_time'] = time();
        $model = new AppointMessage();
        $result = $model->insert($data);
        return result(200, 'ok', $result);
	}

	/**
	 * 检查并定时发送服务消息接口
	 * return boolen
	 */
	public function delayedSending()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Notice/delayedSending.html;

		$now = date('Y-m-d H:i:s');
        $model = new AppointMessage();
        //查找需要通知的数据
        $data = $model->where('status',0)->field('id,openid,type_id,page,form_id,send_time')->where('send_time','elt',$now)->select();
 

        //判断select的多维数组是否为空
        if(count($data) != 0){
  
        	//进行通知
        	 $template = new NoticeModel();
        	 foreach ($data as $k => $v) {
        	 	$res = $template->sendTemplateMsg($v);
        	 }

            return result(200, 'ok', $res);

        }else{
        	
        	return result(200, '该时段无需要处理数据');
        }
		
        
	}
	

	/**
	 * 获取模板接口
	 */
	public function getTemplateList()
	{
        require_params('user_id');

        $template = new NoticeModel();
        $result = $template->getTemplateList();

        return result(200, 'ok', $result);
	}



	/**
	 * 单独通知接收接口
	 */
	public function sendTemplateMsg()
	{
		require_params('openid', 'type_id', 'page', 'form_id','send_time');  //type_id为使用的后台模板id
        $data = Request::param();

        $template = new NoticeModel();
        $result = $template->sendTemplateMsg($data);

        return result(200, 'ok', $result);
	}

}