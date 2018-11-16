<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj\controller\api\v1_0_1;

use controller\BasicController;
use app\khj\service\v1_0_1\Game as GameService;

/**
 * 游戏控制类
 *
 * @author 625575737@qq.com
 */
class Game extends BasicController
{
	public function end(){
		require_params('user_id', 'mode', 'checkpoint', 'is_win');
        $data = Request::param();

        $game_service = new GameService($this->configData);
		$result = $game_service->end($data);

		return result(200, 'ok', $result);
	}
}
