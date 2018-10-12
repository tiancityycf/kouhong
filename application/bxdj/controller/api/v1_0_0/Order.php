<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use think\Db;

use think\facade\Config;
use think\facade\Cache;
/**
 * 订单控制器类
 */
class Order
{
	/**
	 * 订单页面信息
	 * @return json
	 */
	public function index()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Order/index.html?id=9&openid=1;
		require_params('openid','id');    //id为商品的id
		$data = Request::param();

		//查找玩家是否填写过地址信息
		$address_info = Db::name('address')->field('id,phone,addr,region,status,nickname')->where('openid',$data['openid'])->select();

		if(!$address_info){
				$result['address_info'] = [];
		}else{	
			//返回默认地址给前端
			foreach ($address_info as $k => $v) {
				if($v['status'] == 1){
					$result['address_info'] = $v;
				}
			}
			if(!isset($result['address_info'])){
				$result['address_info'] = $v[0];
			}

		}

		//查看该商品的信息
		$result['good_info'] = Db::name('goods')->field('img,title')->where('id',$data['id'])->find();
			
		return result(200, '0k', $result);
		
	}

	/**
	 * 订单页面更多地址
	 * @return json
	 */
	public function more_addr()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/Order/more_addr.html?openid=1
		require_params('openid');    
		$openid = Request::param('openid');

		//查找玩家是否填写过地址信息
		$result['more_addr_info'] = Db::name('address')->field('id,phone,addr,region,status')->where('openid',$openid)->select();

			
		return result(200, '0k', $result);
		
	}
	
}