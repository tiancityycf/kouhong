<?php
namespace validate;

use think\Validate;

class UserLevelWord extends Validate
{
	protected $rule = [
		'user_level_id|用户等级' => ['require', 'integer', 'min' => 1],
		'word_level|题目等级' => ['require', 'integer', 'min' => 0],
		'word_num|题目数量' => ['require', 'integer', 'min' => 0],
		'word_time|答题时间' => ['require', 'float', 'min' => 0],
		'status|状态' => ['require', 'integer', 'between' => '0,1'],
	];
}