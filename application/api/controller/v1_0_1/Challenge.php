<?php

namespace app\api\controller\v1_0_1;

use think\facade\Request;

use app\api\controller\v1_0_1\BasicController;
use app\api\service\v1_0_1\Challenge as ChallengeService;

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
}