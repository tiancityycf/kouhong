<?php

namespace app\khj2\model;

use think\Model;

class UserLipstick extends Model
{

    public function search($params)
    {
    	$query = self::buildQuery();
  
		$query->alias('e');

		if(!empty($params['user_id'])){
			$query->where('e.user_id', $params['user_id']);
		}
		if(!empty($params['openid'])){
			$query->where('u.openid', $params['openid']);
		}
		if(isset($params['status'])){
			$query->where('e.status', $params['status']);
		}

        $query->field('e.*,u.openid,u.nickname,u.avatar');

        $query->join(['t_user'=>'u'],'e.user_id=u.id');

        $query->order('e.id desc');

        return $query;
    }


}
