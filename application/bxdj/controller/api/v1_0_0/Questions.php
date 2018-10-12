<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use think\Db;
use app\bxdj\model\Questions as QuestionsModel;

use think\facade\Config;
use think\facade\Cache;
/**
 * 消息控制器类
 */
class Questions
{
	/**
	 * 查询消息信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Questions/index.html;


		$Questions_info = Cache::get(config('questions_info'));
		
		return result(200, '0k', $Questions_info);
		
	}
	
}