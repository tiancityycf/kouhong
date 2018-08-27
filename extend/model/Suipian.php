<?php

namespace model;

use think\Model;

/**
 * 
 */
class Suipian extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

        return $query;
	}
}