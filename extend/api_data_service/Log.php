<?php

namespace api_data_service;

use model\ChallengeLog;
use model\FormidLog;
use model\LinkLog;
use model\ShareLog;
use model\AdvertisementLog;
use api_data_service\Share as ShareService;

/**
 * 用户日志服务类
 */
class Log
{
	/**
	 * 创建挑战日志
	 * @param  $data 请求数据
	 * @return integer
	 */
	public function createChallengeLog($data)
	{
		$challenge = ChallengeLog::create([
			'user_id' => $data['user_id'],
			'start_time' => time(),
			'create_time' => time(),
		]);

		return $challenge->id;
	}

	/**
	 * 更新挑战日志
	 * @param  $data 请求数据
	 * @return void
	 */
	public function updateChallengeLog($data)
	{
		$time = time();
		ChallengeLog::where('id', $data['challenge_id'])->update([
			'score' => isset($data['score']) ? $data['score'] : 0,
			'successed' => isset($data['successed']) ? $data['successed'] : 0,
			'end_time' => $time,
			'update_time' => $time,
		]);
	}

	/**
	 * 创建微信formid日志
	 * @param  $data 请求数据
	 * @return void
	 */
	public function createFormidLog($data)
	{
		FormidLog::create([
			'user_id' => $data['user_id'],
			'formid' => $data['formid'],
			'create_time' => time(),
		]);
	}

	/**
	 * 创建推广链接日志
	 * @param  $data 请求数据
	 * @return void
	 */
	public function createLinkLog($data)
	{
		LinkLog::create([
			'user_id' => $data['user_id'],
			'app_id' => $data['app_id'],
			'create_time' => time(),
		]);
	}

	/**
	 * 创建广告位日志
	 * @param $data 请求数据
	 * @return void
	 */
	public function createAdvertisementLog($data)
	{
		AdvertisementLog::create([
			'user_id' => $data['user_id'],
			'advertisement_id' => $data['advertisement_id'],
			'create_time' => time(),
		]);
	}

	/**
	 * 创建用户分享记录日志
	 * @param  $data 请求数据
	 * @return array
	 */
	public function createShareLog($data)
	{
		$errorCode = ShareService::decryptedData($data['user_id'], $data['encryptedData'], $data['iv'], $result);
		if ($errorCode == 0) {
			$resultArr = json_decode($result, true);
			$option =  ['gid' => $resultArr['openGId']];
		}

		return array_merge(['error_code' => $errorCode], $option);
	}

}