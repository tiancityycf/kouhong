<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 用户选择统计表模型类
 */
class UserTopicWordCount extends Model
{

    public function topic()
    {
        return $this->hasOne('Topic', 'id', 'topic_id');
    }

    public function topicWord()
    {
        return $this->hasOne('TopicWord', 'id', 'topic_id');
    }

    public function selectTopic()
    {
        return $this->hasOne('SelectTopic', 'id', 'topic_id');
    }
}
