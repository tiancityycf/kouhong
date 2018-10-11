<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;
use think\facade\Config;
use think\facade\Cache;

use think\Db;
/**
 * 用户步数控制器类
 */
class Register
{
	/**
	 * 查询用户对应返回的步数日志信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Register/index.html?openid=1;
		require_params('openid');
		$openid = Request::param('openid');

		//php获取今日开始时间戳和结束时间戳
		$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
		$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		//查看今天是否有生成过水滴
		$hasDrop = Db::name('step')->where(['openid'=>$openid])->where('create_time','between',[$beginToday,$endToday])->find();

		if ($hasDrop) {
			//如果有水滴 说明今天已经签到，拦截其再次生成水滴并签到，返回其缓存的签到天数
			$cache_data = Cache::get($data['openid']);

			return ['message'=>'您今天已经签到','code'=>2000,'register_days'=>$cache_data['register_days']];
		}

		//获取缓存信息
		$config = Cache::get(config('config_key'));

		$register_reward_steps = $config['register_reward_steps']['value'];  //连续签到初始奖励步数值	
		$register_increase_step = $config['register_increase_step']['value'];  //连续签到的加成步数值	


		$yesterday = date("Y-m-d",strtotime("-1 day"));
		$today = date('Y-m-d',time());

		//查看昨天是否签到了  (查看连续签到情况)
		$is_register = Db::name('register')->where(['openid'=>$openid,'create_date'=>$yesterday])->find();

		// 开启事务
        Db::startTrans();
        try {
				//若没有签到，为断签;计数从今天开始
				if(!$is_register){
					//生成水滴 水滴步数为配置步数
					$create_drop = [
						'openid'=>$openid,
						'steps'=>$register_reward_steps,
						'create_time' => time(),
						'comment' => '签到奖励',
					];

					Db::name('step')->insert($create_drop);

					//插入签到数据
					$insert_data = [
						'openid' => $openid,
						'create_date' => $today,
						'create_time' => time(),
						'count_days' => 1
					];
					Db::name('register')->insert($insert_data);

					$register_days = 1;

				}else{
					//若有签到，则需计算其生成水滴的步数值
					$steps = $register_reward_steps + ($is_register['count_days']+1) * $register_increase_step;

					$create_drop = [
						'openid'=>$openid,
						'steps'=>$steps,
						'create_time' => time(),
						'comment' => '签到奖励',
					];

					Db::name('step')->insert($create_drop);

					//插入签到数据
					$insert_data = [
						'openid' => $openid,
						'create_date' => $today,
						'create_time' => time(),
						'count_days' => $is_register['count_days']+1
					];
					Db::name('register')->insert($insert_data);

					$register_days = $is_register['count_days']+1;
				}

			//将连续签到天数存入缓存数组
	    	$cache_data = [
	    		'register_days' => $register_days
	    	];
	    	Cache::set($data['openid'],$cache_data);

			Db::commit();
			return ['message'=>'ok','code'=>1000,'register_days'=>$register_days];

		  } catch (\Exception $e) {

            Db::rollback();
           
            throw new \Exception("系统繁忙");

          }
		
		
	}

	
}