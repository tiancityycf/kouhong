<?php

namespace app\dcqw_xyx\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\dcqw_xyx\Game as GameService;
use controller\BasicController;

/**
 * 词语控制器类
 */
class Challenge extends BasicController
{
    /**
     * 挑战开始
     * @return json
     */
    public function start()
    {
        require_params('user_id');
        $data = Request::param();

        $gameService = new GameService();
        $result = $gameService->start($data);

        return result(200, 'ok', $result);
    }

    /**
     * 挑战结束
     * @return void
     */
    public function end()
    {
        require_params('user_id', 'challenge_id', 'score', 'successed');
        $data = Request::param();

        $gameService = new GameService();
        $result = $gameService->end($data);

        return result(200, 'ok', $result);
    }
}