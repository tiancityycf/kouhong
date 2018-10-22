<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 用户答题记录表模型类
 */
class UserTopicWord extends Model
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
