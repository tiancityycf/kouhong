<?php

namespace model;

use think\Model;
use api_data_service\Config as ConfigService;

/**
 * 用户记录模型类
 */
class UserRecord extends Model
{
	protected $type = [
		'amount' => 'float',
	];
	/**
	 * 获取荣誉榜
	 * @return array
	 */
	public function getHonorList()
	{
		$honorCount = ConfigService::get('honor_list_count');
		$honorList = self::where('success_num', '>', 0)
			->limit($honorCount)
			->order('success_num', 'desc')
			->select();

		$result = [];
		foreach ($honorList as $key => $value) {
			$result[$key] = [
				'avatar' => $value->avatar,
				'nickname' => $value->nickname,
				'success_num' => $value->success_num,
			];
		}

		return $result;
	}

	/**
	 * 获取毅力榜
	 * @return array
	 */
	public function getWillList()
	{
		$willCount = ConfigService::get('will_list_count');
		$willList = self::where('challenge_num', '>', 0)
			->limit($willCount)
			->order('challenge_num', 'desc')
			->select();

		$result = [];
		foreach ($willList as $key => $value) {
			$result[$key] = [
				'user_id' => $value->user_id,
				'avatar' => $value->avatar,
				'nickname' => $value->nickname,
				'challenge_num' => $value->challenge_num,
			];
		}

		return $result;
	}

	public function getWealthList()
	{
		$wealthCount = ConfigService::get('wealth_list_count');
		$wealthList = self::where('amount_total', '>', 0)
			->limit($wealthCount)
			->order('amount_total', 'desc')
			->select();

		$result = [];
		foreach ($wealthList as $key => $value) {
			$result[$key] = [
				'avatar' => $value->avatar,
				'nickname' => $value->nickname,
				'amount_total' => $value->amount_total,
			];
		}

		return $result;
	}

	public function getSuccessList()
	{
		$wealthCount = ConfigService::get('wealth_list_count');
		$wealthList = self::where('success_num', '>', 0)
			->where('user_status', 1)
			->limit($wealthCount)
			->order('success_num', 'desc')
			->select();

		$result = [];
		foreach ($wealthList as $key => $value) {
			$result[$key] = [
				'user_id' => $value->user_id,
				'avatar' => $value->avatar,
				'nickname' => $value->nickname,
				'success_num' => $value->success_num,
			];
		}

		return $result;
	}


	public function getRedpacketList()
	{
		$wealthCount = ConfigService::get('wealth_list_count');
		$wealthList = self::where('redpacket_num', '>', 0)
			->where('user_status', 1)
			->limit($wealthCount)
			->order('redpacket_num', 'desc')
			->select();

		$result = [];
		foreach ($wealthList as $key => $value) {
			$result[$key] = [
				'avatar' => $value->avatar,
				'nickname' => $value->nickname,
				'success_num' => $value->redpacket_num,
			];
		}

		return $result;
	}

	public function pifu()
    {
        return $this->hasOne('Pifu', 'id', 'pifu_id');
    }
}