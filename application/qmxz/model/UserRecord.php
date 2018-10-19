<?php

namespace app\qmxz\model;

use think\Model;

/**
 * 用户记录模型类
 */
class UserRecord extends Model
{
	
    public function search($params)
    {
        $query = self::buildQuery();
        $query->alias('u');

        $query->field('u.id, u.openid, u.avatar,u.nickname, u.user_status,u.gender,s.coins');

        $query->join(['t_step_coin'=>'s'],'u.openid=s.openid');


        if (isset($params['openid']) && $params['openid'] !== '') {
            $query->whereLike('u.openid', "%{$params['openid']}%");
        }

         if (isset($params['nickname']) && $params['nickname'] !== '') {
            $query->whereLike('u.nickname', "%{$params['nickname']}%");
        }

        if (isset($params['gender']) && $params['gender'] !== '') {
            $query->where('u.gender', "{$params['gender']}");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('u.user_status', $params['status'] - 1);
        }

        if (isset($params['coins']) && $params['coins'] !== '') {
            $query->where('s.coins', '>=', $params['coins']);
            $query->order('s.coins desc');
        }

        $query->order('id desc');

        return $query;
    }
}