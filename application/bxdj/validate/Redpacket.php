<?php
namespace app\bxdj\validate;

use think\Validate;

class Redpacket extends Validate
{
	protected $rule = [
		'phone' => 'number|length:11',

	];

	protected $message = [
		'phone.number' => '手机号码必须为数字',
		'phone.length' => '请您填写11位的手机号码',
	];
}