<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 前台话题模型类
 */
class SelectTopic extends Model
{

    public $table = 't_select_topic';
	
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id','topic_id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}