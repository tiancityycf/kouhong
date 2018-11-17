<?php

namespace app\khj\controller\api\v1_0_1;

use think\facade\Request;
use think\Db;

use controller\BasicController;
/**
 * 商品详情页控制器类
 */
class Good extends BasicController
{

    /**
     * 查询所有商品信息
     * @return json
     */

    public function index()
    {
        //前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/good/index.html;
        $goods_info = Db::name('good_cates')->alias('a')->join(['t_goods' => 'b'], 'a.id=b.cate')->where(['b.status' => 1])->order('b.order desc')->field("a.cate_name,b.*")->select();
        $data['good_info'] = $goods_info;

        $data['rules'] = [];
        if(isset($this->configData['rules'])){
            $data['rules'] = $this->configData['rules'];
        }
        return result(200, 'ok', $data);
    }
}