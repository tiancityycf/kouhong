<?php

namespace app\bxdj\controller\api\v1_0_1;

use think\facade\Request;

use think\Db;
use app\bxdj\model\Rules as RulesModel;

use think\facade\Config;
use think\facade\Cache;
/**
 * 消息控制器类
 */
class Rules
{
	/**
	 * 查询消息信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/Rules/index.html;


		$rules_info = Cache::get(config('rules_info'));
		
		return result(200, '0k', $rules_info);
		
	}
	
}