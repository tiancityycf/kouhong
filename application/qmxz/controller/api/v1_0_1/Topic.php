<?php

namespace app\qmxz\controller\api\v1_0_1;

use app\qmxz\service\v1_0_1\Topic as TopicService;
use controller\BasicController;
use think\facade\Request;

/**
 * 普通场接口控制器类
 */
class Topic extends BasicController
{
    /**
     * 检测金币是否不足接口
     * @return boolean
     */
    public function checkGold()
    {
        require_params('user_id', 'type', 'topic_id');
        $data = Request::param();

        $topicService = new TopicService($this->configData);
        $result       = $topicService->checkGold($data);

        return result(200, 'ok', $result);
    }

    /**
     * 分类接口
     * @return boolean
     */
    public function topicCate()
    {
        require_params('user_id');
        $userId = Request::param('user_id');

        $topicService = new TopicService($this->configData);
        $result       = $topicService->topicCate($userId);

        return result(200, 'ok', $result);
    }

    /**
     * 列表接口
     * @return boolean
     */
    public function topicList()
    {
        require_params('user_id');
        $data = Request::param();

        $topicService = new TopicService($this->configData);
        $result       = $topicService->topicList($data);

        return result(200, 'ok', $result);
    }

    /**
     * 问题列表和评论列表接口
     * @return boolean
     */
    public function questionList()
    {
        require_params('user_id', 'topic_id');
        $data = Request::param();

        //问题列表
        $topicService  = new TopicService($this->configData);
        $question_list = $topicService->questionList($data);

        //评论列表
        $comment_list = $topicService->commentList($data);

        //普通场亚宝消耗
        $default_consume_gold = $topicService->defaultConsumeGold();

        $result = [
            'default_consume_gold' => $default_consume_gold,
            'question_list'        => $question_list,
            'comment_list'         => $comment_list,
        ];

        return result(200, 'ok', $result);
    }

    /**
     * 每题提交问题答案接口
     * @return boolean
     */
    public function submitAnswer()
    {
        require_params('user_id', 'topic_id', 'topic_word_id', 'user_select', 'is_pass');
        $data = Request::param();

        $topicService = new TopicService($this->configData);
        //问题相关
        $question_info = $topicService->getQuestion($data);

        //提交问题
        $answer_result = $topicService->submitAnswer($data);

        //评论列表
        $comment_list = $topicService->commentList($data);

        $result = [
            'question_info' => $question_info,
            'answer_result' => $answer_result,
            'comment_list'  => $comment_list,
        ];

        return result(200, 'ok', $result);
    }

    /**
     * 用户提交评论接口
     * @return boolean
     */
    public function submitComment()
    {
        require_params('user_id', 'topic_id', 'topic_word_id', 'user_comment');
        $data = Request::param();

        //提交评论
        $topicService = new TopicService($this->configData);
        $result       = $topicService->submitComment($data);

        return result(200, 'ok', $result);
    }

    /**
     * 获取用户话题答题结果
     * @return boolean
     */
    public function userTopicResult()
    {
        require_params('user_id', 'topic_id');
        $data = Request::param();

        //获取用户话题答题结果
        $topicService = new TopicService($this->configData);
        $result       = $topicService->userTopicResult($data);

        return result(200, 'ok', $result);
    }
}
