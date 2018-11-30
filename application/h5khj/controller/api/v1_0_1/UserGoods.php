<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\h5khj\controller\api\v1_0_1;

use think\facade\Request;
use controller\BasicController;
use app\h5khj\service\v1_0_1\UserGoods as UserGoodsService;
use app\h5khj\model\Address as AddressModel;

/**
 * 游戏控制类
 *
 * @author
 */
class UserGoods extends BasicController
{
	//领取
	public function receive()
	{
		require_params('user_id', 'challenge_id', 'nickname', 'phone', 'addr','region');
		$data = Request::param();

        $adata['user_id'] = $data['user_id'];
        $adata['nickname'] = $data['nickname'];
        $adata['phone'] = $data['phone'];
        $adata['addr'] = $data['addr'];
        $adata['region'] = $data['region'];
        $adata['create_time'] = time();
        $AddressModel = new AddressModel();
        $AddressModel->save($adata);
        $data['address_id'] = $AddressModel->id;

		$service = new UserGoodsService($this->configData);
		$result = $service->receive($data);

		return result(200, 'ok', $result);
	}

}
