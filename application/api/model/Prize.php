<?php

namespace app\api\model;

use think\Model;

/**
 * 奖品模型类
 */
class Prize extends Model
{
	/**
	 * 获取奖品列表
	 * @return array
	 */
	public function getPrizeList()
	{
		$prizeList = self::where('status', 1)->select();

		$result = [];
		foreach ($prizeList as $key => $prize) {
			$result[$key] = [
				'prize_id' => $prize['id'],
				'name' => $prize['name'],
				'img' => $prize['img'],
			];
		}
		
		return $result;
	}
}