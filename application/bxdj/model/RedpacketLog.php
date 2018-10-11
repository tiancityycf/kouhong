<?php

namespace app\bxdj\model;

use think\Model;


/**
 * 用户红包模型类
 */
class RedpacketLog extends Model
{
    
   public function search($params)
    {
        $query = self::buildQuery();
        $query->field('r.*,g.activity_id,g.get_reward');
        $query->alias('r');
        $query->join(['t_group_persons'=>'g'],'r.openid = g.openid','right');

        if (isset($params['openid']) && $params['openid'] !== '') {
            $query->whereLike('r.openid', "%{$params['openid']}%");
        }
      
        if (isset($params['activity_id']) && $params['activity_id'] !== '') {
            $query->where('activity_id', "{$params['activity_id']}");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', "{$params['status']}");
        }


        $query->order('r.create_time desc');

        return $query;
    }
    
}