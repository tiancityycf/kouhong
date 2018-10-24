<?php

namespace app\qmxz\controller\api\v1_0_1;

use app\qmxz\service\v1_0_1\Special as SpecialService;
use think\facade\Request;

/**
 * 整点场接口控制器类
 */
class Special//extends BasicController

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
        require_params('user_id', 'special_id', 'special_word_id', 'user_select');
        $data = Request::param();

        //提交问题
        $specialService = new SpecialService();
        $result         = $specialService->submitAnswer($data);

        return result(200, 'ok', $result);
    }

    /**
     * 通关请求接口
     * @return boolean
     */
    public function passAnswer()
    {
        require_params('user_id', 'special_id');
        $data = Request::param();

        //通关请求
        $specialService = new SpecialService();
        $result         = $specialService->passAnswer($data);

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

        //通关请求
        $specialService = new SpecialService();
        $result         = $specialService->answerResult($data);

        return result(200, 'ok', $result);
    }

}
