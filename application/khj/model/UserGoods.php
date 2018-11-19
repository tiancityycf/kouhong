<?php

namespace app\khj\model;

use think\Model;

class UserGoods extends Model
{
	public function address()
    {
        return $this->hasOne('Address', 'id', 'address_id');
    }

    public function goods()
    {
        return $this->hasOne('Goods', 'id', 'goods_id');
    }

    public function search($params)
    {
        $query = self::buildQuery();

        $query->alias('e');

        foreach (['openid', 'is_shiping'] as $key) {
            (isset($params[$key]) && $params[$key] !== '') && $query->whereLike($key, "%{$params[$key]}%");
        }

        $query->field('e.*,u.openid,u.avatar,g.title,g.img,a.nickname,a.phone,a.addr,a.region');

        $query->join(['t_user'=>'u'],'e.user_id=u.id');

        $query->join(['t_address'=>'a'],'e.address_id=a.id');

        $query->join(['t_goods'=>'g'],'e.goods_id=g.id');

        $query->order('id desc');

        return $query;
    }
}