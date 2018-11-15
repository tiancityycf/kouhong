<?php
namespace app\qmxz\validate;

use think\Validate;

class TopicWord extends Validate
{
    protected $rule = [
        'topic_id|话题' => 'require',
        'options|选项'   => 'require',
    ];
}