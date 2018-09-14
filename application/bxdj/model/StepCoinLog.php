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

        $query->alias('s');

        $query->field('s.*, u.avatar,u.nickname');

        $query->join(['t_user'=>'u'],'s.openid=u.openid','left');
      
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