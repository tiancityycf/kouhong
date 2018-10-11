<?php

namespace app\bxdj\controller\api\v1_0_1;

use think\facade\Request;

use think\Db;
/**
 * 用户步数控制器类
 */
class Steps
{
	/**
	 * 查询用户对应返回的步数日志信息
	 * @return json
	 */
	public function logs()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_1/steps/logs.html?openid=1;
		require_params('openid');
		$openid = Request::param('openid');

		$result = Db::name('step')->where('openid',$openid)->order('id desc')->select();

		foreach ($result as $key => $value) {
			$result[$key]['create_time'] = date('Y-m-d H:i',$value['create_time']);
		}
		return result(200, '0k', $result);
	}

	
}