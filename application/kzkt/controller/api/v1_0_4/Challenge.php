<?php

namespace app\kzkt\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\v1_0_3\Challenge as ChallengeService;
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

        $challengeService = new ChallengeService();
        $result = $challengeService->start($data);

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

        $challengeService = new ChallengeService();
        $result = $challengeService->end($data);

        return result(200, 'ok', $result);
    }


    /**
     * 挑战开始
     * @return json
     */
    public function lianxi()
    {
        require_params('user_id');
        $data = Request::param();

        $challengeService = new ChallengeService();
        $result = $challengeService->lianxi($data);

        return result(200, 'ok', $result);
    }
}