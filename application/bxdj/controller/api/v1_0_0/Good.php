<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;

use think\Db;

use think\facade\Config;
use think\facade\Cache;
/**
 * 商品详情页控制器类
 */
class Good
{
	/**
	 * 查询具体商品信息与其兑换过的信息
	 * @return json
	 */
	public function good_detail()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/good/good_detail.html?id=1;
		require_params('id');  //id指的是good_id
		$good_id = Request::param('id');

		//获取缓存商品信息
        $goods_info = Cache::get(config('goods_info'));

        foreach ($goods_info as $k1 => $v1) {
        	foreach ($v1 as $k2 => $v2) {
        		if($v2['id']==$good_id){
        			 $good_detail = $v2;
        			 break 2;
        		}
        	}
        }

        $exchanger = Db::name('exchange_log')->alias('e')->join('t_user u','e.openid = u.openid')->field('nickname,avatar,e.create_time')->where('good_id',$good_id)->select();
        //echo Db::name('exchange_log')->getLastSql();
        //dump($exchanger);die;
        foreach ($exchanger as $key => $value) {
			$exchanger[$key]['create_time'] = date('Y-m-d H:i',$value['create_time']);
		}

        $arr['good_detail'] = $good_detail;
        $arr['exchanger'] = $exchanger;
        
        return result(200, '0k', $arr);
	}

	
}