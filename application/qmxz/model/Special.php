<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场模型类
 */
class Special extends Model
{
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        if (isset($params['create_time']) && $params['create_time'] !== '') {
            list($start_create_time, $end_create_time) = explode(' - ', $params['create_time']);
            $query->whereBetweenTime('display_time', "{$start_create_time}", "{$end_create_time}");
        }else{
            $start = strtotime(date('Y-m-d 00:00:00'));
            $end   = strtotime(date('Y-m-d 23:59:59'));
            $query->where('display_time', 'between', [$start, $end]);
        }

        $query->order('id desc');

        return $query;
    }
}
