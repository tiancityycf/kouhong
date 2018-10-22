<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场模型类
 */
class UserSpecial extends Model
{
	public function special()
    {
        return $this->hasOne('Special', 'id', 'special_id');
    }

    public function userRecord()
    {
        return $this->hasOne('UserRecord', 'user_id', 'user_id');
    }
}