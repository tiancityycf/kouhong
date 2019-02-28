<?php

namespace app\khj2\model;

use think\Model;

/**
 * 整点场模型类
 */
class Config extends Model
{
	/**
	 * 获取所有配置
	 * @return array
	 */
	public static function getAll()
	{
		return self::where('status', 1)->select();
	}
}
