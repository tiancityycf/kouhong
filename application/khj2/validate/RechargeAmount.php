<?php
namespace app\khj2\validate;

use think\Validate;

class RechargeAmount extends Validate
{
    protected $rule = [
        'money|金额' => 'require|float',
        // 'gold|金币' => 'require|number',
        'sort|排序' => 'require|number'
    ];
}