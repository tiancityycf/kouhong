<?php

namespace app\qmxz\model;

use think\Model;

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

    	$query->field('u.id, u.openid, u.nickname,ur.gender, ur.money, ur.gold, ur.gold');

    	$query->join(['t_user_record'=>'ur'],'ur.user_id=u.id');

        if (isset($params['openid']) && $params['openid'] !== '') {
        	$query->whereLike('u.openid', "%{$params['openid']}%");
        }

        if (isset($params['nickname']) && $params['nickname'] !== '') {
        	$query->whereLike('u.nickname', "%{$params['nickname']}%");
        }

        if (isset($params['gold']) && $params['gold'] !== '') {
            $query->where('ur.gold', '>=', $params['gold']);
            $query->order('ur.gold desc');
        }

        if (isset($params['money']) && $params['money'] !== '') {
            $query->where('ur.money', '>=', $params['money']);
            $query->order('ur.money desc');
        }
  
        $query->order('id desc');

        return $query;
    }


}