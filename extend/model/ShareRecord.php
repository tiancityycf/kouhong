<?php

namespace model;

use think\Model;

/**
 * 用户分享日志模型类
 */
class ShareRecord extends Model
{
	public function userRecord()
    {
        return $this->hasOne('UserRecord', 'user_id', 'click_user_id');
    }
}