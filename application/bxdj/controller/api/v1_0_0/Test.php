<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use think\facade\Config;
use think\facade\Cache;
use think\Db;
use think\Controller;

/**
 * 用户控制器类
 */
class Test extends Controller
{
	public function test(){
$data['openid'] = 'oFGa94nfezRdM1PhLcD9lbBNIg3g';
$cache_data = Cache::get($data['openid']);
var_dump($cache_data);
die;

		$today = '2018-10-12';

		$exchange_history = Db::name('step_coin_log')->where(['openid'=>'oFGa94nfezRdM1PhLcD9lbBNIg3g','exchange_date'=>$today])->select();
		//$exchange_history = Db::name('step_coin_log')->where(['openid'=>'oFGa94npLbwOXpuA5eqbCE8fukbQ','exchange_date'=>$today])->select();

		if(!empty($exchange_history)){
			//!若用户当天进行了多次兑换 该步数信息会减去之前兑换的步数信息
			$today_steps = 0;
			//对一维数组或多维数组的判断
			if(count($exchange_history)==count($exchange_history,1)){

				$today_steps = $exchange_history['steps'];   //$today_steps为当天历史兑换的总步数

			}else{

				foreach ($exchange_history as $k => $v) {
					$today_steps += $v['steps'];   //$today_steps为当天历史兑换的总步数
				}
			}

			echo $today_steps;
		}
	}



}
