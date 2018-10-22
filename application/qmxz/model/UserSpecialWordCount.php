<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 整点场模型类
 */
class UserSpecialWordCount extends Model
{
	public function special()
    {
        return $this->hasOne('Special', 'id', 'special_id');
    }

    public function specialWord()
    {
        return $this->hasOne('SpecialWord', 'id', 'special_word_id');
    }
}