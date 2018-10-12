<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;
use think\Db;
use api_data_service\v1_0_0\Notice as NoticeModel;

use app\bxdj\model\Activity as ActivityModel;

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
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Notice/appointMessage.html?openid=1&type_id=9&page=index&form_id=2&send_time=2018-09-28 10:00:00;
        require_params('openid', 'type_id', 'page', 'form_id','send_time');  //type_id为使用的后台模板id
        $data = Request::param();
        $model = new AppointMessage();
        //type_id:9 为签到提醒 type_id：10 为团队赛开始时间提示  type_id：11 为活动结束服务消息提醒  
        $today = date("Y-m-d",time());
        $activityModel = new ActivityModel;
        if($data['type_id'] == 10){
        	
        	//活动开始的时间要大于发送服务消息的时间
        	$activity = $activityModel->where(['status'=>1])->where('start_date','>',"$today")->find();
        	if(!$activity){
        		return ['message'=>'活动已开始','code'=>1000];
        	//判断该用户是否已经预约过服务消息通知
        	}else if($appointed = $model->where(['type_id'=>10,'openid'=>$data['openid']])->find()){
        		return ['message'=>'您已经预约过该活动通知','code'=>1001];
        	}else{
        		//将活动开始服务消息通知时间确定 （每个活动开始的第一天早上9点）
        		$data['send_time'] = $activity['start_date'] . ' 09:00:00';
        	}
        }else if($data['type_id'] == 11){
            //活动结束服务消息获取form_id时间的规则：1.大于活动开始的时间 2.小于活动本身结束的时间
            $activity = $activityModel->where(['status'=>1])->where('start_date','<=',"$today")->where('end_date','>=',"$today")->find();

            if(!$activity){
                return ['message'=>'活动未开始或已经结束','code'=>2000];
            //判断该用户是否已经预约过服务消息通知
            }else if($appointed = $model->where(['type_id'=>11,'openid'=>$data['openid']])->find()){
                return ['message'=>'您已经预约过该活动通知','code'=>2001];
            }else{
                //将活动结束服务消息通知时间确定 （每个活动结束后的一天的早上9点）
                $infrom_endday = date("Y-m-d",strtotime("+1 day",strtotime($activity['end_date'])));
                $data['send_time'] = $infrom_endday . ' 09:00:00';
            }

        }
        $data['create_time'] = time();
        $result = $model->insert($data);
        return result(200, 'ok', $result);
	}

	/**
	 * 检查并定时发送服务消息接口(只发送预约签到提醒的服务消息)
	 * return boolen
	 */
	public function delayedSending()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Notice/delayedSending.html;

		$now = date('Y-m-d H:i:s');
        $model = new AppointMessage();
        //查找需要通知的数据
        $data = $model->where(['status'=>0,'type_id'=>9])->field('id,openid,type_id,page,form_id,send_time')->where('send_time','elt',$now)->select();

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
	 * 检查并定时发送服务消息接口(只发送团队赛开始时间提示的服务消息)
	 * return boolen
	 */
	public function delayedSendingGroup()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Notice/delayedSendingGroup.html;

        $model = new AppointMessage();

        $activityModel = new ActivityModel;
        //获取活动开始时间信息
        $activity = $activityModel->field('end_date')->where(['status'=>1])->find();
        $send_date = $activity['start_date'] . ' 09:00:00';
        //查找需要通知的数据
        $data = $model->where(['status'=>0,'type_id'=>10])->field('id,openid,type_id,page,form_id,send_time')->where('send_time','eq',$send_date)->select();
    
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
     * 检查并定时发送服务消息接口(只发送团队赛结束时间提示的服务消息)
     * return boolen
     */
    public function delayedSendingGroupEnd()
    {
        //前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Notice/delayedSendingGroupEnd.html;

        $model = new AppointMessage();

        $activityModel = new ActivityModel;

        $yesterday = date("Y-m-d",strtotime("-1 day"));
        //为了测试，强制设置时间
        $yesterday = '2018-09-30';
     
        //获取活动开始时间信息
        $activity = $activityModel->field('end_date')->where('end_date','eq',$yesterday)->find();
        
        $infrom_endday = date("Y-m-d",strtotime("+1 day",strtotime($activity['end_date'])));

        $send_date = $infrom_endday . ' 09:00:00';
        
        //查找需要通知的数据
        $data = $model->where(['status'=>0,'type_id'=>11])->field('id,openid,type_id,page,form_id,send_time')->where('send_time','eq',$send_date)->select();
    
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