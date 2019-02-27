<?php
namespace app\khj2\validate;

use think\Validate;

class DailyTask extends Validate
{
    protected $rule = [
        'gold|金币'    => 'require|number',
        'times|完成次数' => 'require|number',
        'sort|排序'    => 'require|number',
    ];
}
