<?php
namespace app\qmxz\validate;

use think\Validate;

class SpecialWordWarehouse extends Validate
{
    protected $rule = [
        'options|选项'   => 'require',
    ];
}
