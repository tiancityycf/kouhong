<?php

namespace app\bxdj\model;

use think\Model;


/**
 * 用户记录模型类
 */
class StepCoinLog extends Model
{
	 public function search($params)
    {
        $query = self::buildQuery();

        $query->alias('u');

        $query->field('u.id, u.openid, u.avatar,u.nickname, u.user_status,s.coins');

        $query->join(['t_step_coin'=>'s'],'u.openid=s.openid');
      
        if (isset($params['openid']) && $params['openid'] !== '') {
            $query->where('openid', "{$params['openid']}");
        }

        if (isset($params['nickname']) && $params['nickname'] !== '') {
            $query->where('nickname', $params['nickname']);
        }

        $query->order('id desc');

        return $query;
    }
   
}