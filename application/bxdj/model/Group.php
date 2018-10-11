<?php

namespace app\bxdj\model;

use think\Model;
//use api_data_service\Config as ConfigService;

/**
 * 用户记录模型类
 */
class Group extends Model
{
	
   public function search($params)
    {
        $query = self::buildQuery();
        $query->alias('g');
        $query->field('g.*,a.img,a.status');
        $query->join(['t_activity'=>'a'],'g.activity_id = a.id');

        if (isset($params['id']) && $params['id'] !== '') {
            $query->where('id', "{$params['id']}");
        }
      
        if (isset($params['activity_id']) && $params['activity_id'] !== '') {
            $query->where('activity_id', "{$params['activity_id']}");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('g.status', "{$params['status']}");
        }

        $query->order('group_steps desc');

        return $query;
    }
}