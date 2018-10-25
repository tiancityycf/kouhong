<?php

namespace app\qmxz\controller\api\v1_0_1;

use app\qmxz\service\v1_0_1\Index as IndexService;
use controller\BasicController;
use think\facade\Request;

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
        $result       = $indexService->index($data);

        return result(200, 'ok', $result);
    }
}
