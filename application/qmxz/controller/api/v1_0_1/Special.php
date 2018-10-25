<?php

namespace app\qmxz\controller\api\v1_0_1;

use app\qmxz\service\v1_0_1\Special as SpecialService;
use controller\BasicController;
use think\facade\Request;

/**
 * 整点场接口控制器类
 */
class Special extends BasicController
{

    /**
     * 列表接口
     * @return boolean
     */
    public function specialList()
    {
        require_params('user_id');
        $userId = Request::param('user_id');

        $specialService = new SpecialService();
        $result         = $specialService->specialList($userId);

        return result(200, 'ok', $result);
    }

    /**
     * 进入答题页扣除金币接口
     * @return boolean
     */
    public function deductGold()
    {
        require_params('user_id', 'special_id');
        $data = Request::param();

        $specialService = new SpecialService();
        $result         = $specialService->deductGold($data);

        return result(200, 'ok', $result);
    }

    /**
     * 问题列表接口
     * @return boolean
     */
    public function questionList()
    {
        require_params('user_id', 'special_id');
        $data = Request::param();

        //问题列表
        $specialService = new SpecialService();
        $result         = $specialService->questionList($data);

        return result(200, 'ok', $result);
    }

    /**
     * 每题提交问题答案接口
     * @return boolean
     */
    public function submitAnswer()
    {
        require_params('user_id', 'special_id', 'special_word_id', 'user_select', 'is_pass');
        $data = Request::param();

        //每题提交问题答案接口
        $specialService = new SpecialService();
        $result         = $specialService->submitAnswer($data);

        return result(200, 'ok', $result);
    }

    /**
     * 整点场答题接口
     * @return boolean
     */
    public function answerResult()
    {
        require_params('user_id', 'special_id');
        $data = Request::param();

        //整点场答题接口
        $specialService = new SpecialService();
        $result         = $specialService->answerResult($data);

        return result(200, 'ok', $result);
    }

    /**
     * 整点场抽奖页信息
     * @return boolean
     */
    public function prizePage()
    {
        require_params('user_id', 'special_id');
        $data = Request::param();

        //整点场抽奖页信息
        $specialService = new SpecialService();
        $result         = $specialService->prizePage($data);

        return result(200, 'ok', $result);
    }

    /**
     * 整点场抽奖页抽奖
     * @return boolean
     */
    public function luckDraw()
    {
        require_params('user_id', 'special_id');
        $data = Request::param();

        //整点场抽奖页抽奖
        $specialService = new SpecialService();
        $result         = $specialService->luckDraw($data);

        return result(200, 'ok', $result);
    }

    /**
     * 使用兑换码兑奖
     * @return boolean
     */
    public function cashPrize()
    {
        require_params('user_id', 'code');
        $data = Request::param();

        //使用兑换码兑奖
        $specialService = new SpecialService();
        $result         = $specialService->cashPrize($data);

        return result(200, 'ok', $result);
    }

}
