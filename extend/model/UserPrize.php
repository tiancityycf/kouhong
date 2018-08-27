<?php

namespace model;

use think\Model;

/**
 * 用户领取奖品模型类
 */
class UserPrize extends Model
{
	/**
	 * 模型关联
	 * @return object \app\api\model\Prize
	 */
	public function prize()
	{
		return $this->hasOne('Prize', 'id', 'prize_id');
	}

	/**
	 * 获取用户已领取奖品列表
	 * @param  $userId 用户id
	 * @return array
	 */
	public function getUserPrizeList($userId)
	{
		$userPrizeList = self::where('user_id', $userId)->select();

		$result = [];
		foreach ($userPrizeList as $key => $userPrize) {
			$result[$key] = [
				'name' => $userPrize['prize']['name'],
				'img' => $userPrize['prize']['img'],
				'time' => $userPrize['create_time'],
			];
		}

		return $result;
	}
}