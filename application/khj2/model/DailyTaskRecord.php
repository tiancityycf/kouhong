<?php

namespace app\khj2\model;

use think\Model;

/**
 * 每日任务记录模型类
 */
class DailyTaskRecord extends Model
{
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id', 'user_id', 'task_id', 'type'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        if (isset($params['addtime']) && $params['addtime'] !== '') {
            $times_arr = explode(' - ', $params['addtime']);
            $addstart  = $times_arr[0];
            $addend    = $times_arr[1];
            $query->where('addtime', 'between', [$addstart, $addend]);
        }

        $query->order('addtime desc');

        return $query;
    }
}
