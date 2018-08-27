<?php

namespace model;

use think\Model;

/**
 * 用户等级设置模型类
 */
class UserLevelWord extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

        foreach (['user_level_id', 'word_level', 'word_num', 'word_time'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        $query->order('id', 'desc');

        return $query;
	}
}