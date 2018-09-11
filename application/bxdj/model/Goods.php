<?php

namespace app\bxdj\model;

use think\Model;
//use api_data_service\Config as ConfigService;

/**
 * 用户记录模型类
 */
class Goods extends Model
{
	
    public function search($params)
    {
        $query = self::buildQuery();
      
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

        $query->order('id desc');

        return $query;
    }
}