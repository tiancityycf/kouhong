<?php

namespace app\qmxz\controller\api\v1_0_1;

use think\facade\Request;

use think\Db;

use think\facade\Config;
use think\facade\Cache;

use app\qmxz\model\Goods as GoodsModel;
/**
 * 商品详情页控制器类
 */
class Good
{

    /**
     * 查询所有商品信息
     * @return json
     */

    public function index()
    {
        //前台测试链接：https://qmxz.wqop2018.com/qmxz/api/v1_0_1/good/index.html;
        $goods_info = Cache::get(config('goods_info'));
        $config = Cache::get(config('config_key'));

        $arr['banners'] = json_decode($config['good_banners']['value']);
        $arr['good_info'] = $goods_info;

        return result(200, '0k', $arr);
    }
	/**
	 * 查询具体商品信息与其兑换过的信息
	 * @return json
	 */
	public function good_detail()
	{
		//前台测试链接：https://qmxz.wqop2018.com/qmxz/api/v1_0_1/good/good_detail.html?id=40;
		require_params('id');  //id指的是good_id
		$good_id = Request::param('id');

		 //获取缓存商品信息  商品的库存信息实时更新  不能查询缓存数据
        //$goods_info = Cache::get(config('goods_info'));
        $good_detail = Db::name('goods')->where('id',$good_id)->find();

        dump($good_detail);die;

        $exchanger = Db::name('exchange_log')->alias('e')->join('t_user u','e.openid = u.openid')->field('nickname,avatar,e.create_time')->where('good_id',$good_id)->order('e.id desc')->limit(5)->select();
        //echo Db::name('exchange_log')->getLastSql();
        //dump($exchanger);die;
        foreach ($exchanger as $key => $value) {
			$exchanger[$key]['create_time'] = date('Y-m-d H:i',$value['create_time']);
		}

        $arr['good_detail'] = $good_detail;
        $arr['exchanger'] = $exchanger;
        
        return result(200, '0k', $arr);
	}


     /**
     * 用户金币兑换商品方法
     * @return boolen
     */
    public function exchange_good()
    {
        //前台测试链接：https://qmxz.wqop2018.com/qmxz/api/v1_0_1/good/exchange_good.html?openid=1&id=2&address_id=8;
        require_params('user_id','id','address_id'); //id值good_id address_id为地址id
        $data = Request::param();
        
        $GoodsModel = new GoodsModel();

        $goodsInfo = $GoodsModel->field('id,stock,price')->where('id',$data['id'])->find();

        $userInfo =  Db::name('user_record')->where('user_id',$data['user_id'])->find();

        if(!$goodsInfo){

            return ['message'=>'无该商品','code'=>1000];

        }else if($goodsInfo['stock'] <= 0){

            return ['message'=>'商品库存不足','code'=>1001];

        }else if($goodsInfo['price'] > $userInfo['gold']){

            return ['message'=>'用户金币低于商品价格','code'=>1002];

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
            return ['message'=>'成功兑换商品','code'=>2000];
        }    
   
    }

	
}