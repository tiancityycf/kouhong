<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj2\controller\api\v1_0_1;

use think\facade\Request;
use controller\BasicController;
use app\khj2\service\v1_0_1\Game as GameService;

/**
 * 游戏控制类
 */
class Game extends BasicController
{

	public function end()
	{
		require_params('user_id', 'success', 'sign','timestamp');
        $data = Request::param();

        if($this->sign($data)){
            $game_service = new GameService();
            $result = $game_service->end($data);
        }else{
            $result =  [
                'status' => 0,
                'msg'    => '签名失败',
            ];
        }
		return result(200, 'ok', $result);
	}

    public function lipstick()
    {
        require_params('user_id');
        $data = Request::param();

        $game_service = new GameService();
        $result = $game_service->lipstick($data);

        return result(200, 'ok', $result);
    }

    private function sign($data)
    {
        //游戏结束 调用 end的接口  再加个参数 sign
        //算法如下：md5("user_id="+user_id+"timestamp="+timestamp);
        $sign = md5("user_id=".$data['user_id']."timestamp=".$data['timestamp']);
        if($sign==$data['sign']){
            return true;
        }
        return false;
    }
}
