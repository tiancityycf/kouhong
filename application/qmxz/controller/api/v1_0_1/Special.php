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

        $specialService = new SpecialService($this->configData);
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

        $specialService = new SpecialService($this->configData);
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
        $specialService = new SpecialService($this->configData);
        $question_list  = $specialService->questionList($data);

        //评论列表
        $comment_list = $specialService->commentList($data);

        //整点场押宝消耗
        $timing_consume_gold = $specialService->timing_consume_gold();

        $result = [
            'timing_consume_gold' => $timing_consume_gold,
            'question_list'       => $question_list,
            'comment_list'        => $comment_list,
        ];

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
        $specialService = new SpecialService($this->configData);
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
        $specialService = new SpecialService($this->configData);
        $result         = $specialService->answerResult($data);

        return result(200, 'ok', $result);
    }

    /**
     * 用户提交评论接口
     * @return boolean
     */
    public function submitComment()
    {
        require_params('user_id', 'special_id', 'special_word_id', 'user_comment');
        $data = Request::param();

        //提交评论
        $specialService = new SpecialService($this->configData);
        $result         = $specialService->submitComment($data);

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
        $specialService = new SpecialService($this->configData);
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
        $specialService = new SpecialService($this->configData);
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
        $specialService = new SpecialService($this->configData);
        $result         = $specialService->cashPrize($data);

        return result(200, 'ok', $result);
    }

    /**
     * 获取用户获奖纪录
     * @return boolean
     */
    public function userPrize()
    {
        require_params('user_id');
        $userId = Request::param('user_id');

        //获取用户获奖纪录
        $specialService = new SpecialService($this->configData);
        $result         = $specialService->userPrize($userId);

        return result(200, 'ok', $result);
    }

    /**
     * 获取用户整点场纪录
     * @return boolean
     */
    public function userSpecialRecord()
    {
        require_params('user_id');
        $userId = Request::param('user_id');

        //获取用户整点场纪录
        $specialService = new SpecialService($this->configData);
        $result         = $specialService->userSpecialRecord($userId);

        return result(200, 'ok', $result);
    }

}
