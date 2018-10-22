<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 用户答题记录表模型类
 */
class UserTopicWord extends Model
{

    public $table = 't_user_topic_word';
	
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}