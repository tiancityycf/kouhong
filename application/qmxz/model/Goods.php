<?php

namespace app\qmxz\model;

use think\Model;
//use api_data_service\Config as ConfigService;

/**
 * 商品模型类
 */
class Goods extends Model
{
	
    public function search($params)
    {
        $query = self::buildQuery();
        
        $query->alias('g');

        $query->field('g.*,c.cate_name');

        $query->join(['t_good_cates'=>'c'],'g.cate=c.id');

        if (isset($params['cate']) && $params['cate'] !== '') {
            $query->where('cate', "{$params['cate']}");
        }

        if (isset($params['onsale']) && $params['onsale'] !== '') {
            $query->where('onsale', $params['onsale']);
        }

        if (isset($params['price']) && $params['price'] !== '') {
            $query->where('price', '>=', $params['price']);
            $query->order('price desc');
        }

        if (isset($params['is_partner']) && $params['is_partner'] !== '') {
            $query->where('is_partner', $params['is_partner']);
        }

        $query->order('id desc');

        return $query;
    }
}