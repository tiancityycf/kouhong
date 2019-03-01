<?php

namespace app\khj2\model;

use think\Model;

class Video extends Model
{

    public function search($params)
    {
    	$query = self::buildQuery();
		$query->alias('a');

        $query->field('a.user_id,u.openid,count(1) as c');

        $query->join(['t_user'=>'u'],'u.id=a.user_id');

		$query->where('a.is_end', 1);

        if (isset($params['openid']) && $params['openid'] !== '') {
			$params['openid'] = trim($params['openid']);
			$query->whereLike('u.openid', "%{$params['openid']}%");
        }
        if (isset($params['create_time']) && $params['create_time'] !== '') {
			list($start_create_time, $end_create_time) = explode(' - ', $params['create_time']);
			$query->whereBetweenTime('a.create_time', "{$start_create_time}", "{$end_create_time}");
        }
        $query->group('a.user_id');

        return $query;
    }


}
