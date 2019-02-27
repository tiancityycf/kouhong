<?php

namespace app\khj2\model;

use think\Model;

/**
 * 每日任务模型类
 */
class DailyTask extends Model
{
	public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('sort');

        return $query;
    }
}