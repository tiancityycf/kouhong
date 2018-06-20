<?php

namespace app\admin\model;

use think\Model;

/**
 * 用户等级设置模型类
 */
class UserLevel extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

		$query->withCount('levelWord');
		$query->withSum('levelWord', 'word_num');

		foreach (['title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        foreach (['success_num'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        return $query;
	}

	public function levelWord()
    {
        return $this->hasMany('UserLevelWord', 'user_level_id');
    }
}