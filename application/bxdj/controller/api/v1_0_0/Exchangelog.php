<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use think\facade\Config;
use think\facade\Cache;

use app\bxdj\model\Goods as GoodsModel;

use think\Db;
/**
 * 用户步数控制器类
 */
class Exchangelog
{
	/**
	 * 查询用户燃力币兑换商品的日志信息
	 * @return json
	 */
	public function logs()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Exchangelog/logs.html?openid=1;
		require_params('openid');
		$openid = Request::param('openid');

		$result = Db::name('exchange_log')->alias('e')->join(['t_goods'=>'g'],'e.good_id=g.id')->field('e.*,g.img,g.title')->where('openid',$openid)->select();
		
		foreach ($result as $key => $value) {
			$result[$key]['create_time'] = date('Y-m-d',$value['create_time']);
		}
		
		return result(200, '0k', $result);
	}

	/**
	 * 计算用户步数兑换燃力币数值
	 * @return boolen
	 */
	public function exchange_coin()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Exchangelog/exchange_coin.html?openid=1&steps=1200;
		require_params('openid','steps');

		$data = Request::param();
		//获取配置信息
        $config = Cache::get(config('config_key'));

        $exchange_rate = $config['exchange_rate']['value'];
        //目前兑换比例为6/10000；
        $coins = number_format($data['steps'] * $exchange_rate,4);

        //$coins = floatval($coins);

        if($coins){
        	 $res1 = Db::name('step_coin')->where('openid',$data['openid'])->setInc('coins',$coins);

        	 $insert_data = [
        	 		'openid' => $data['openid'],
        	 		'steps'  => $data['steps'],
        	 		'get_coins' => $coins,
        	 		'create_time' => time()
        	 ];

        	 $res2 = Db::name('step_coin_log')->insert($insert_data);
        	 if($res1 !== false && $res2 !== false){
        	 		return result(200, '0k', 1);
        	 }
        }
	}

	/**
	 * 用户燃力币兑换商品方法
	 * @return boolen
	 */
	public function exchange_good()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Exchangelog/exchange_good.html?openid=1&id=2;
		require_params('openid','id'); //id值good_id

		$data = Request::param();
		
		$GoodsModel = new GoodsModel();

		$goodsInfo = $GoodsModel->field('id,stock,price')->where('id',$data['id'])->find();

		$userInfo =  Db::name('step_coin')->where('openid',$data['openid'])->find();

		if(!$goodsInfo){

			return ['message'=>'无该商品','code'=>1000];

		}else if($goodsInfo['stock'] == 0){

			return ['message'=>'商品库存不足','code'=>1001];

		}else if($goodsInfo['price'] > $userInfo['coins']){

			return ['message'=>'用户燃力币低于商品价格','code'=>1002];

		}else{
			 // 开启事务
            Db::startTrans();
            try {
            	//商品表减对应商品库存
            	$GoodsModel->where('id',$data['id'])->setDec('stock');
            	//用户燃力币表减对应的燃力币值
                Db::name('step_coin')->where('openid',$data['openid'])->setDec('coins',$goodsInfo['price']);
                //记录燃力币兑换商品日志信息
                $update_data = [
                	'openid' => $data['openid'],
                	'good_id' => $goodsInfo['id'],
                	'used_coins' => $goodsInfo['price'],
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