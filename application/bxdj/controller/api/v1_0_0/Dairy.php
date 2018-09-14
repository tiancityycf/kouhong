<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use app\bxdj\model\Dairy as DairyModel;

use think\facade\Config;
use think\facade\Cache;
/**
 * 用户燃力币日记控制器类
 */
class Dairy
{
	/**
	 * 查询用户对应返回的日记信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/dairy/index.html?openid=1&steps=10001;
		require_params('openid','steps');
		$data = Request::param();
		
		$dairy_info = Cache::get(config('dairy_info'));
		
		foreach ($dairy_info as $k => $v) {
			if($v['min_step'] <= $data['steps'] && $v['max_step']>$data['steps']){
				
				return result(200, '0k', $v);
			}
		}

		//若对应步数没在配置中，返回配置第一项日记信息
		return result(200, '0k', $dairy_info[0]);
	}
}