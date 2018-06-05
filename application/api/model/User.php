<?php

namespace app\api\model;

use think\Model;

/**
 * 用户模型类
 */
class User extends Model
{
	/**
	 * 模型关联
	 * @return \app\api\model\UserRecord
	 */
	public function userRecord()
	{
		return $this->hasOne('UserRecord');
	}
}