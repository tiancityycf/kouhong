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
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/Activity/index.html&openid=oFGa94hlqh7ZTPk90Z0uihyEpGPc

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
		//查询活动是否存在且申请时间是否在活动截止时间之前
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
					foreach ($history_info as $k => $v) {
						//活动开始时间与结束时间转前端格式
						$history_info[$k]['start_date'] = date('Y/m/d',strtotime($v['start_date']));
						$history_info[$k]['end_date'] = date('Y/m/d',strtotime($v['end_date']));
					}
					$return_data['history_info'] = $history_info;
				}	

			}else{
				$return_data['history_info'] = [];
			}
			
			//活动已经结束 
			if (!$activity = $activityModel->where(['status'=>1])->where('end_date','>=',"$today")->find()) {
				
				$finished_activity = $activityModel->where(['status'=>0])->order('id desc')->find();
				//活动开始时间与结束时间转前端格式
				$finished_activity['start_date'] = date('Y/m/d',strtotime($finished_activity['start_date']));
				$finished_activity['end_date'] = date('Y/m/d',strtotime($finished_activity['end_date']));

				$return_data['activity_info'] = $finished_activity;

				return ['message'=>'活动不存在或已经结束','code'=>2001,'data'=>$return_data,'my_group'=>-1];
			//活动正在进行
			}else{

				//2.判断该查看的玩家是创建团队逻辑还是查看自己团队的逻辑
				$is_create = $groupPersonsModel->where(['openid'=>$data['openid'],'activity_id'=>$activity['id']])->where('join_date','<=',$activity['end_date'])->find();

				//活动开始时间与结束时间转前端格式
				$activity['start_date'] = date('Y/m/d',strtotime($activity['start_date']));
				$activity['end_date'] = date('Y/m/d',strtotime($activity['end_date']));

				$return_data['activity_info'] = $activity;

				if(!$is_create){
					//创建自己团队逻辑
					return ['message'=>'您可以创建自己团队','code'=>2002,'data'=>$return_data,'my_group'=>0];

				}else{
					//查看自己团队的逻辑
					return ['message'=>'您已有团队,请查看','code'=>2003,'data'=>$return_data,'my_group'=>$is_create['group_id']];
				}

			}

		}
	}


	/**
	 * 查询团队赛的历史记录
	 * @return json
	 */
	public function check_history_info()
	{
		require_params('openid','group_id');  
		$data = Request::param();

		$userModel = new UserModel;
		$groupModel = new GroupModel;
		$activityModel = new ActivityModel;
		$groupPersonsModel = new GroupPersonsModel;

		//找到该用户结束的团队赛排名成绩
		$history_group_info = $groupModel->alias('g')->join('t_group_persons a','g.id = a.group_id')->field('g.id,g.group_steps,g.rank,g.nums,g.status,a.contribute_steps,a.get_reward')->where(['g.id'=>$data['group_id'],'g.status'=>0])->find();

		return ['message'=>'历史活动信息','code'=>3000,'data'=>$history_group_info];
	}
}