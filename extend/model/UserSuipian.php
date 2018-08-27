<?php

namespace model;

use think\Model;

/**
 * 
 */
class UserSuipian extends Model
{
	public function userRecord()
    {
        return $this->hasOne('User', 'user_id', 'user_id');
    }

    public function user()
    {
        return $this->hasOne('User', 'id', 'user_id');
    }


    public function search($params)
	{
		$query = self::buildQuery();

        foreach (['user_id'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->where($key, $params[$key]);
        }

        return $query;
	}
}