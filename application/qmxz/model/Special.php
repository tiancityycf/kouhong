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

        $start = strtotime(date('Y-m-d 00:00:00'));
        $end   = strtotime(date('Y-m-d 23:59:59'));

        $query->where('display_time', 'between', [$start, $end]);

        foreach (['title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}
