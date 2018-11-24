<?php

namespace app\khj\controller\api\v1_0_1;

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
        //按照价格升序
        $goods_info = Db::name('good_cates')->alias('a')->join(['t_goods' => 'b'], 'a.id=b.cate')->where(['b.status' => 1])->order('b.order asc')->field("a.cate_name,b.*")->select();
        foreach($goods_info as $k=>$v){
            $goods_info[$k]['imgs'] = Db::name('good_imgs')->where(['product_id' => $v['id']])->field("img")->select();
        }
        $data['good_info'] = $goods_info;

        $data['rules'] = [];
        if(isset($this->configData['rules'])){
            $data['rules'] = $this->configData['rules'];
        }

        $data['notice'] = [];
        if(isset($this->configData['notice'])){
            $data['notice']['title'] = isset($this->configData['notice'][0])?$this->configData['notice'][0]:'';
            $data['notice']['content'] = isset($this->configData['notice'][1])?$this->configData['notice'][1]:'';
        }
        return result(200, 'ok', $data);
    }
}