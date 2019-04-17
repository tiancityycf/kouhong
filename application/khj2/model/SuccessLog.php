<?php

namespace app\khj2\model;

use think\Model;

/**
 * 挑战日志模型类
 */
class SuccessLog extends Model
{
	public function goods()
    {
        return $this->hasOne('Goods', 'id', 'goods_id');
    }
}