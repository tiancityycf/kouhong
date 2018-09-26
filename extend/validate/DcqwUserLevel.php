<?php
namespace validate;

use think\Validate;

class DcqwUserLevel extends Validate
{
	protected $rule = [
		'title|标题' => 'require',
		'success_num|需要通过次数' => ['require', 'integer', 'min' => 0],
		'score|通关分数' => ['require', 'integer', 'min' => 0],
		'status|状态' => ['require', 'integer', 'between' => '0,1'],
	];
}