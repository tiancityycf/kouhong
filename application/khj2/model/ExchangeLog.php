<?php

namespace app\khj2\model;

use think\Model;


/**
 * 用户记录模型类
 */
class ExchangeLog extends Model
{
	
    public function search($params)
    {
        $query = self::buildQuery();

        $query->alias('e');

        $query->field('e.*,u.openid,u.avatar,g.title,g.img,a.nickname,a.phone,a.addr,a.region');

        $query->join(['t_user'=>'u'],'e.user_id=u.id');

        $query->join(['t_address'=>'a'],'e.address_id=a.id');

        $query->join(['t_goods'=>'g'],'e.good_id=g.id');


        if (isset($params['openid']) && $params['openid'] !== '') {
            $query->whereLike('u.openid', "%{$params['openid']}%");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('e.status', $params['status']);
        }

        $query->order('id desc');

        return $query;
    }
}