<?php
namespace app\qmxz\validate;

use think\Validate;

class SpecialWarehouse extends Validate
{
    protected $rule = [
        'prize_id|奖品' => 'require',
        'img|图片'     => 'require',
    ];
}
