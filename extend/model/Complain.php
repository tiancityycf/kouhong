<?php

namespace model;

use think\Model;

/**
 * 用户投诉模型类
 */
class Complain extends Model
{

	public function user()
    {
        return $this->hasOne('User', 'id', 'user_id');
    }

    public function userRecord()
    {
        return $this->hasOne('UserRecord', 'user_id', 'user_id');
    }


    public function search($params)
    {
    	$query = self::buildQuery();
    	$query->alias('c');
    	$query->field('c.id, c.user_id, c.type, c.create_time, u.openid, u.nickname, ur.user_status');
    	
		$query->join(['t_user'=>'u'],'u.id=c.user_id');
		$query->join(['t_user_record'=>'ur'],'ur.user_id=c.user_id');

    	foreach (['type'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        if (isset($params['user_id']) && $params['user_id'] !== '') {
        	$query->where('c.user_id', $params['user_id']);
        }

        if (isset($params['openid']) && $params['openid'] !== '') {
        	$query->whereLike('u.openid', "%{$params['openid']}%");
        }

        if (isset($params['nickname']) && $params['nickname'] !== '') {
        	$query->whereLike('u.nickname', "%{$params['nickname']}%");
        }


        $query->order('id desc');

        return $query;
    }
}