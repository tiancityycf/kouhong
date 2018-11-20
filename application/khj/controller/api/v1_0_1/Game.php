<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj\controller\api\v1_0_1;

use think\facade\Request;
use controller\BasicController;
use app\khj\service\v1_0_1\Game as GameService;

/**
 * 游戏控制类
 *
 * @author 625575737@qq.com
 */
class Game extends BasicController
{
	public function start()
	{
		require_params('user_id', 'goods_id');
		$data = Request::param();

		$game_service = new GameService($this->configData);
		$result = $game_service->start($data);

		return result(200, 'ok', $result);
	}

	public function end()
	{
		require_params('user_id', 'challenge_id', 'goods_id', 'is_win','sign');
        $data = Request::param();

        if($this->sign($data)){
            $game_service = new GameService($this->configData);
            $result = $game_service->end($data);
        }else{
            $result =  [
                'status' => 0,
                'msg'    => '签名失败',
            ];
        }
		return result(200, 'ok', $result);
	}

    public function challenge_log()
    {
        require_params('user_id');
        $data = Request::param();

        $game_service = new GameService($this->configData);
        $result = $game_service->challenge_log($data);

        return result(200, 'ok', $result);
    }

    private function sign($data)
    {
        //游戏结束 调用 end的接口  再加个参数 sign
        //算法如下：md5("user_id="+user_id+"challenge_id="+challenge_id+"goods_id="+goods_id+"is_win="+is_win);
        $sign = md5("user_id=".$data['user_id']."challenge_id=".$data['challenge_id']."goods_id=".$data['goods_id']."is_win=".$data['is_win']);
        if($sign==$data['sign']){
            return true;
        }
        return false;
    }
}
