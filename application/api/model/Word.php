<?php

namespace app\api\model;

use think\Model;

/**
 * 词语模型类
 */
class Word extends Model
{
	/**
	 * 获取指定难度下的所有词语id
	 * @param  integer $level 难度等级
	 * @return array
	 */
	public static function getAllIdsByLevel($level)
	{
        return self::where('status', 1)->where('level', $level)->column('id');
	}
}