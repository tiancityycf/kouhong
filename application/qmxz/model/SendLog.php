<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 发送记录模型类
 */
class SendLog extends Model
{
    public $table = 't_send_log';

    public function search($params)
    {
        $query = self::buildQuery();

        $query->order('id asc');

        return $query;
    }
}
