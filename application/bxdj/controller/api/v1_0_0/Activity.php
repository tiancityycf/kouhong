<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use app\bxdj\model\Group as GroupModel;
use app\bxdj\model\Activity as ActivityModel;
use app\bxdj\model\GroupPersons as GroupPersonsModel;
use model\User as UserModel;

use think\facade\Config;
use think\facade\Cache;
/**
 * 用户燃力币日记控制器类
 */
class Activity
{
	/**
	 * 查询目前开展的活动信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Activity/index.html&openid=1;

		require_params('openid');  
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//活动参与计算步数的时间
		$today = date("Y-m-d",time());

		//查询用户是否存在
		if( !$user = $userModel->where('openid',$data['openid'])->find() ){

				return ['message'=>'创建人不存在','code'=>2000];
		//查询最近一次的活动
		}else if (!$activity = $activityModel->order('id desc')->find()) {
				
				return ['message'=>'活动不存在或已经结束','code'=>2001];

		}else{
			//若通过上述判断，1.展示团队赛之前季度的历史记录 2.判断该查看的玩家是创建团队逻辑还是查看自己团队的逻辑
			//1.展示团队赛之前季度的历史记录 （1）找到该用户参与的组 （2）找到该用户结束的团队赛排名成绩
			//（1）找到该用户参与的组
			$history_groupId = $groupPersonsModel->field('group_id')->where('openid',$data['openid'])->select();
			if($history_groupId){
				$group_ids = '';
				foreach ($history_groupId as $k => $v) {
					 $group_ids .= $v['group_id'] . ',';
				}
				//将最后一个','号去除
				$group_ids = rtrim($group_ids,",");
				//（2）找到该用户结束的团队赛排名成绩
				$history_info = $groupModel->alias('g')->join('t_activity a','g.activity_id = a.id')->field('g.id,g.rank,g.group_steps,a.title,a.start_date,a.end_date')->where('g.id','in',$group_ids)->where('g.status',0)->select();

				if(count($history_info) == 0){
					$return_data['history_info'] = [];
				}else{
					$return_data['history_info'] = $history_info;
				}	

			}else{
				$return_data['history_info'] = [];
			}
		
			
			//2.判断该查看的玩家是创建团队逻辑还是查看自己团队的逻辑
			$is_create = $groupPersonsModel->where(['openid'=>$data['openid'],'activity_id'=>$activity['id']])->where('join_date','<=',$activity['end_date'])->find();

			$retrun_data['activity_info'] = $activity;

			if(!$is_create){
				//创建自己团队逻辑
				return ['message'=>'您可以创建自己团队','code'=>2002,'data'=>$retrun_data,'my_group'=>0];

			}else{
				//查看自己团队的逻辑
				return ['message'=>'您已有团队,请查看','code'=>2003,'data'=>$retrun_data,'my_group'=>$is_create['group_id']];
			}

		}
	}
}