<?php

namespace app\khj2\model;

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

		$query->field('u.*,s.id as sid');

    	$query->join(['t_user_record'=>'ur'],'ur.user_id=u.id');

        if (isset($params['id']) && $params['id'] !== '') {
        	$query->where('u.id', $params['id']);
        }
        if (isset($params['openid']) && $params['openid'] !== '') {
        	$query->whereLike('u.openid', "%{$params['openid']}%");
        }
        if (isset($params['invite_id']) && $params['invite_id'] !== '') {
        	$query->where('u.invite_id', $params['invite_id']);
        }
        if (isset($params['sign_type']) && $params['sign_type'] !== '') {
        	$query->where('u.sign_type', $params['sign_type']);
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
	$dday = date('ymd');
	$query->leftJoin(['t_sign'=>'s'],'s.user_id=u.id and s.dday='.$dday);
        if (isset($params['today_sign']) && $params['today_sign'] !== '') {
	    if($params['today_sign']==1){
		$query->where('s.id','is null');
	    }elseif($params['today_sign']==2){
		$query->where('s.id','null');
	    }
	}
  
        $query->group('u.id');
        $query->order('u.id desc');

        return $query;
    }


}
