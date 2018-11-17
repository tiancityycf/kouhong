<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj\controller\api\v1_0_1;

use controller\BasicController;
use app\khj\service\v1_0_1\UserGoods as UserGoodsService;

/**
 * 游戏控制类
 *
 * @author 625575737@qq.com
 */
class UserGoods extends BasicController
{
	//领取
	public function receive()
	{
		require_params('user_id', 'goods_id', 'address_id');
		$data = Request::param();

		$service = new UserGoodsService($this->configData);
		$result = $service->receive($data);

		return result(200, 'ok', $result);
	}

	//记录
	public function user_goods_list()
	{
		require_params('user_id');
		$data = Request::param();

		$service = new UserGoodsService($this->configData);
		$result = $service->user_goods_list($data);

		return result(200, 'ok', $result);
	}
}
