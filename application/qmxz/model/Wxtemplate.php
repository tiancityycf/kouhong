<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 模板消息模型类
 */
class Wxtemplate extends Model
{
    public $table = 't_template';

    public function search($params)
    {
        $query = self::buildQuery();
        foreach (['id', 'template_id', 'title'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->order('id desc');

        return $query;
    }
}
