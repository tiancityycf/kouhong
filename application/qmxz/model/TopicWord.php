<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 话题题目模型类
 */
class TopicWord extends Model
{

    public $table = 't_topic_word';
	
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id','topic_id','title','des'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}