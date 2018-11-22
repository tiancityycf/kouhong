<?php

namespace app\khj\controller\api\v1_0_1;

use app\khj\service\v1_0_1\Index as IndexService;
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
        //前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/index/index
        $data['switch'] = 0 ; //1:审核中 0:审核通过
        //控制ios android显示不同内容
        $data['isIOS'] = 1 ; 
        return result(200, 'ok', $data);
    }
}
