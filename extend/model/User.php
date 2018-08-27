<?php

namespace model;

use think\Model;

/**
 * 
 */
class User extends Model
{
	public function userRecord()
    {
        return $this->hasOne('UserRecord', 'user_id', 'id');
    }

    public function search($params)
    {
    	$query = self::buildQuery();
    	$query->alias('u');

    	$query->field('u.id, u.openid, u.nickname, ur.user_status, ur.chance_num, ur.challenge_num, ur.success_num, ur.amount, ur.amount_total, ur.user_level');

    	$query->join(['t_user_record'=>'ur'],'ur.user_id=u.id');


    	if (isset($params['user_id']) && $params['user_id'] !== '') {
        	$query->where('u.id', $params['user_id']);
        }

        if (isset($params['openid']) && $params['openid'] !== '') {
        	$query->whereLike('u.openid', "%{$params['openid']}%");
        }

         if (isset($params['nickname']) && $params['nickname'] !== '') {
        	$query->whereLike('u.nickname', "%{$params['nickname']}%");
        }

        if (isset($params['status']) && $params['status'] !== '') {
        	$query->where('ur.user_status', $params['status'] - 1);
        }

        $query->order('id desc');

        return $query;
    }
}