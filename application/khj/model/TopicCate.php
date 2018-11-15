<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 话题分类模型类
 */
class TopicCate extends Model
{

    public $table = 't_topic_cate';
	
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id','title','des'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}