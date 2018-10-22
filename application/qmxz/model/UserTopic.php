<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 用户是否打过这个话题模型类
 */
class UserTopic extends Model
{

    public $table = 't_user_topic';
	
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