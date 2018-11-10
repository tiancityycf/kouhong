<?php

namespace app\qmxz\controller\api\v1_0_1;

use think\facade\Request;
use controller\BasicController;
use app\qmxz\service\v1_0_1\Notice as NoticeService;

/**
 * 服务通知控制器类
 */
class Notice extends BasicController
{
	/**
	 * 获取模板接口
	 */
	public function getTemplateList()
	{
        require_params('user_id');

        $template = new NoticeService($this->configData);
        $result = $template->getTemplateList();

        return result(200, 'ok', $result);
	}

	/**
	 * 通知接收接口
	 */
	public function sendTemplateMsg()
	{
        require_params('id', 'special_id', 'user_id', 'page', 'form_id');
        $data = Request::param();

        $template = new NoticeService($this->configData);
        $result = $template->sendTemplateMsg($data);

        return result(200, 'ok', $result);
	}
}