<?php
namespace app\qmxz\validate;

use think\Validate;

class Topic extends Validate
{
    protected $rule = [
        'cate_id|分类' => 'require',
        'title|标题'   => 'require',
        'img|图片'     => 'require',
    ];
}
