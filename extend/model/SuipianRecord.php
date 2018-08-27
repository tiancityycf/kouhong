<?php

namespace model;

use think\Model;

/**
 * 
 */
class SuipianRecord extends Model
{
	public function search($params)
	{
		$query = self::buildQuery();

        return $query;
	}
}