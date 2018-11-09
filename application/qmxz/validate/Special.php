<?php
namespace app\qmxz\validate;

use think\Validate;

class Special extends Validate
{
    protected $rule = [
        'prize_id|奖品' => 'require',
        'banners|轮播图' => 'require',
    ];
}
