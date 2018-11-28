<?php

namespace app\h5khj\controller\api\v1_0_1;

use app\h5khj\service\v1_0_1\Index as IndexService;
use controller\BasicController;
use think\Db;
use think\facade\Request;

/**
 * 首页接口控制器类
 */
class Index extends BasicController
{
    /**
     * 拉去首页切换的开关
     * @return json
     */

    public function index()
    {
        //前台测试链接：https://h5khj.wqop2018.com/h5khj/api/v1_0_1/index/index
        $data = Request::param();
        $switch = 0;
        $iospay = 0;
        if(isset($data['version'])){
            $switch = isset($this->configData['switch_'.$data['version']])?$this->configData['switch_'.$data['version']]:0;
            $iospay = isset($this->configData['ios_pay_'.$data['version']])?$this->configData['ios_pay_'.$data['version']]:0;
        }
        $data['switch'] = $switch ; //1:审核中 0:审核通过
        //0-开启IOS支付  1-关闭IOS支付
        $data['isIOS'] = $iospay ;
        return result(200, 'ok', $data);
    }
}
