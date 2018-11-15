<?php

namespace app\khj\model;

use think\Model;

/**
 * 模板消息模型类
 */
class TemplateMsg extends Model
{
    public $table = 't_template_msg';

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
