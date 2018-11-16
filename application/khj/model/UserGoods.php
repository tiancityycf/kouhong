<?php

namespace app\khj\model;

use think\Model;

/**
 * 挑战日志模型类
 */
class UserGoods extends Model
{
	public function address()
    {
        return $this->hasOne('Address', 'id', 'address_id');
    }

    public function goods()
    {
        return $this->hasOne('Goods', 'id', 'goods_id');
    }
}