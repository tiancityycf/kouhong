<?php

namespace app\khj\controller\api\v1_0_1;

use think\facade\Request;
use think\Db;
use think\facade\Cache;

use app\khj\model\Goods as GoodsModel;
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
	/**
	 * 查询具体商品信息与其兑换过的信息
	 * @return json
	 */
	public function good_detail()
	{
		//前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/good/good_detail.html?id=40;
		require_params('id');  //id指的是good_id
		$good_id = Request::param('id');

		 //获取缓存商品信息  商品的库存信息实时更新  不能查询缓存数据

        $stock = Db::name('goods')->field('stock')->where('id',$good_id)->find();

        $exchanger = Db::name('exchange_log')->alias('e')->join('t_user u','e.user_id = u.id')->field('nickname,avatar,e.create_time')->where('good_id',$good_id)->order('e.id desc')->limit(10)->select();

        foreach ($exchanger as $key => $value) {
			$exchanger[$key]['create_time'] = date('Y-m-d H:i',$value['create_time']);
		}

        $arr['stock'] = $stock;
        $arr['exchanger'] = $exchanger;
     
        return result(200, '0k', $arr);
	}


     /**
     * 用户金币兑换商品方法
     * @return boolen
     */
    public function exchange_good()
    {
        //前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/good/exchange_good.html?user_id=1&id=2&address_id=8;
        require_params('user_id','id','address_id'); //id值good_id address_id为地址id
        $data = Request::param();

        $GoodsModel = new GoodsModel();

        $goodsInfo = $GoodsModel->field('id,stock,price')->where('id',$data['id'])->find();

        $userInfo =  Db::name('user_record')->where('user_id',$data['user_id'])->find();

        if(!$goodsInfo){
           $res = ['code'=>1000];
           return result(200, '无该商品', $res);

        }else if($goodsInfo['stock'] <= 0){
            $res =  ['code'=>1001];
            return result(200, '商品库存不足', $res);

        }else if($goodsInfo['price'] > $userInfo['gold']){
            $res = ['code'=>1002];
            return result(200, '用户金币低于商品价格', $res);

        }else{
             // 开启事务
            Db::startTrans();
            try {
                //商品表减对应商品库存
                $GoodsModel->where('id',$data['id'])->setDec('stock');
                //用户金币表减对应的金币值
                Db::name('user_record')->where('user_id',$data['user_id'])->setDec('gold',$goodsInfo['price']);
                //记录金币兑换商品日志信息
                $update_data = [
                    'user_id' => $data['user_id'],
                    'good_id' => $goodsInfo['id'],
                    'address_id' => $data['address_id'],
                    'used_gold' => $goodsInfo['price'],
                    'create_time' => time()
                ];
                Db::name('exchange_log')->insert($update_data);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \Exception("系统繁忙");
            }
            return result(200, '成功兑换商品',['code' => 1500]);
        }    
   
    }

    /**
     * 兑换成功
     * @return boolen
     */
    public function good_info()
    {
        //前台测试链接：http://khj.com/khj/api/v1_0_1/good/good_info.html?user_id=9&id=8;
        require_params('user_id','id'); //id值good_id 
        $data = Request::param();
        
        $GoodsModel = new GoodsModel();

        $good_img = $GoodsModel->field('img')->where('id',$data['id'])->find();
        
        $gold = Db::name('user_record')->field('gold')->where('user_id',$data['user_id'])->find();

        $arr['good_img'] = $good_img;
        $arr['gold'] = $gold;
     
        return result(200, '0k', $arr);

    }


    /**
     * 查询用户金币兑换商品的日志信息
     * @return json
     */
    public function logs()
    {
        //前台测试链接：http://khj.com/khj/api/v1_0_1/good/logs.html?user_id=1;
        require_params('user_id');
        $user_id = Request::param('user_id');

        $result = Db::name('exchange_log')->alias('e')->join(['t_goods'=>'g'],'e.good_id=g.id')->field('e.*,g.img,g.title')->where('user_id',$user_id)->order('id desc')->select();
        
        foreach ($result as $key => $value) {
            $result[$key]['create_time'] = date('Y-m-d',$value['create_time']);
        }
        
        return result(200, '0k', $result);
    }

}