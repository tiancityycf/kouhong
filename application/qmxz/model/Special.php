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
            // list($start_create_time, $end_create_time) = explode(' - ', $params['create_time']);
            $times_arr = explode(' - ', $params['create_time']);
            $start = $times_arr[0];
            $end = $times_arr[1];
            // $query->whereBetweenTime('display_time', "{$start_create_time}", "{$end_create_time}");
        }else{
            $start = strtotime(date('Y-m-d 00:00:00'));
            $end   = strtotime(date('Y-m-d 23:59:59'));
        }

        if (isset($params['day']) && $params['day'] !== '') {
            switch ($params['day']) {
                case '-1':
                    $start = strtotime(date('Y-m-d H:i:s',strtotime("-1 day")));
                    $end   = strtotime(date('Y-m-d 00:00:00'));
                    break;
                case '1':
                    $start = strtotime(date('Y-m-d 00:00:00'));
                    $end   = strtotime(date('Y-m-d 23:59:59'));
                    break;
                case '2':
                    $start = strtotime(date('Y-m-d 00:00:00',strtotime("+1 day")));
                    $end   = strtotime(date('Y-m-d 23:59:59',strtotime("+1 day")));
                    break;
                case '3':
                    $start = strtotime(date('Y-m-d 00:00:00',strtotime("+2 day")));
                    $end   = strtotime(date('Y-m-d 23:59:59',strtotime("+2 day")));
                    break;
            }
        }

        $query->where('display_time', 'between', [$start, $end]);

        $query->order('id desc');

        return $query;
    }
}
