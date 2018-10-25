<?php

namespace app\qmxz\controller\api\v1_0_1;

use think\facade\Request;

use app\qmxz\service\v1_0_1\Index as IndexService;
use app\qmxz\model\User as UserModel;
use controller\BasicController;

/**
 * 首页接口控制器类
 */
class Index extends BasicController
{
	/**
	 * 首页接口
	 * @return boolean
	 */
	public function index()
	{
		//echo "<pre>"; print_r($this->configData);exit();
		require_params('user_id');
		$data = Request::param();

		$indexService = new IndexService($this->configData);
		$result = $indexService->index($data);

		return result(200, 'ok', $result);
	}
}
