<?php

namespace app\bxdj\model;

use think\Model;
//use api_data_service\Config as ConfigService;

/**
 * 用户记录模型类
 */
class ExchangeLog extends Model
{
	
    public function search($params)
    {
        $query = self::buildQuery();

        $query->alias('e');

        $query->field('e.*,u.avatar,g.title,g.img');

        $query->join(['t_user'=>'u'],'e.openid=u.openid');

        $query->join(['t_goods'=>'g'],'e.good_id=g.id');
      
        if (isset($params['openid']) && $params['openid'] !== '') {
            $query->whereLike('e.openid', "%{$params['openid']}%");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('e.status', $params['status']);
        }

        $query->order('id desc');

        return $query;
    }
}