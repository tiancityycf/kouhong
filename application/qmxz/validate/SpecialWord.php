<?php
namespace app\qmxz\validate;

use think\Validate;

class SpecialWord extends Validate
{
    protected $rule = [
        'special_id|整点场次' => 'require',
        'options|选项'   => 'require',
    ];
}
