<?php

namespace app\khj\model;

use think\Model;

/**
 * 充值金额模型类
 */
class RechargeAmount extends Model
{
    public function search($params)
    {
        $query = self::buildQuery();

        foreach (['id','title', 'caption', 'status'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('sort');

        return $query;
    }
}