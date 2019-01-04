<?php

namespace app\xtxpd\controller\api\v1_0_1;

use app\xtxpd\service\v1_0_1\Index as IndexService;
use controller\BasicController;
use think\Db;
use think\facade\Request;

/**
 * 首页接口控制器类
 */
class Index extends BasicController
{
    /**
     * 拉去首页配置 审核开关等
     * @return json
     */
    public function index()
    {
        //前台测试链接：https://xtxpd.wqop2018.com/xtxpd/api/v1_0_1/index/index
        $data = Request::param();
        if(isset($data['version'])){
            $check = isset($this->configData['check_'.$data['version']])?$this->configData['check_'.$data['version']]:0;
        }
        $data['check'] = $check ; //1:审核中 0:审核通过
        $data['config'] = $this->configData ;
        return result(200, 'ok', $data);
    }
}
