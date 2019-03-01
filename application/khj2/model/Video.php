<?php

namespace app\khj2\model;

use think\Model;

class Video extends Model
{

    public function search($params)
    {
    	$query = self::buildQuery();

        $query->order('id desc');

        return $query;
    }


}
