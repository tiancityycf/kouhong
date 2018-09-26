<?php
namespace validate;

use think\Validate;

class DcqwUserLevelWord extends Validate
{
	protected $rule = [
		'user_level_id|用户等级' => ['require', 'integer', 'min' => 1],
		'score_mark|分数段' => ['require', 'integer', 'min' => 0],
		'max|最大跳跃次数' => ['require', 'integer', 'min' => 0],
		'time|跳跃时间' => ['require', 'float', 'min' => 0],
		'status|状态' => ['require', 'integer', 'between' => '0,1'],
	];
}