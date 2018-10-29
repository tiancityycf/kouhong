<?php

namespace app\qmxz\controller\api\v1_0_1;

use app\qmxz\service\v1_0_1\Index as IndexService;
use controller\BasicController;
use think\facade\Request;
use think\Db;

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

    /**
     * 用户中心接口
     * @return boolean
     */
    public function center()
    {
        //echo "<pre>"; print_r($this->configData);exit();
        require_params('user_id');
        $user_id = Request::param('user_id');
        $result['user_info'] = Db::name('user_record')->field('avatar,nickname,gold,money')->where('user_id',$user_id)->find();
        $rules = $this->configData['rules'];

        foreach ($rules as $key => $value) {
            list($result['rules'][$key]['title'],$contents) = explode('|', $value); 
            if(strpos($contents,'&')){
                 $result['rules'][$key]['content'] = explode('&',$contents); 
            }else{

                $result['rules'][$key]['content'][] = $contents;
            };
        }
        $result['common_problems'] = $this->configData['common_problems'];
        
        return result(200, 'ok', $result);
    }
}
