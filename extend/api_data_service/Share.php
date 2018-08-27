<?php

namespace api_data_service;

use think\Db;
use think\facade\Config;
use api_data_service\Log as LogService;
use api_data_service\Config as ConfigService;
use model\User as UserModel;
use model\UserRecord as UserRecordModel;
use model\ShareLog as ShareLogModel;
use model\ShareCount as ShareCountModel;

/**
 * 用户分享服务类
 */
class Share
{
	public static $OK = 0;
	public static $IllegalAesKey = -41001;
	public static $IllegalIv = -41002;
	public static $IllegalBuffer = -41003;
	public static $DecodeBase64Error = -41004;

	/**
	 * 分享
	 * @param  $data 请求数据
	 * @return boolean
	 */
	public function share($data)
	{
		switch ($data['share_type']) {
			case '1':
				return $this->shareUser($data);
				break;
			case '2':
				return $this->shareGroup($data);
				break;
			default:
				throw new \Exception();
				break;
		}
	}

	/**
	 * 分享给用户
	 * @param  $data 请求数据
	 * @return void
	 */
	private function shareUser($data)
	{
		$chanceNum = 0;
		// 判断是否开启分享到用户奖励挑战机会
		if (ConfigService::get('open_share_user') == 1) {
			$chanceNum = $this->shareGetChance($data['user_id'], 1);
		}

		return ['status' => 1, 'chance_num' => $chanceNum, 'diff' => 0];
	}

	/**
	 * 分享到群
	 * @param  $data 请求数据
	 * @return void
	 */
	private function shareGroup($data)
	{
		$status = 0;
		$chanceNum = 0;
		$diff = 0;
		$LogService = new LogService();
		$shareInfo = $LogService->createShareLog($data);
		$limit = ConfigService::get('qun_limit_today') ? ConfigService::get('qun_limit_today') : 20;

		if ($shareInfo['error_code'] == 0) {
			$todayShare = ShareLogModel::where('user_id', $data['user_id'])
				->whereTime('create_time', '>=', 'today')
				->distinct(true)
				->field('gid')
				->select()
				->toArray();
			$count = $this->getCount($shareInfo['gid']);
			if (!in_array($shareInfo['gid'], array_column($todayShare, 'gid')) && $count <= $limit) {
				$diff = 1;
				$chanceNum = $this->shareGetChance($data['user_id'], 2);

				if ($chanceNum != 0) {
					ShareLogModel::create([
						'user_id' => $data['user_id'],
						'gid' => $shareInfo['gid'],
						'create_time' => time(),
					]);

					$this->addCount($shareInfo['gid']);
				}
				
			}

			

			$status = 1;
		}

		return ['status' => $status, 'chance_num' => $chanceNum, 'diff' => $diff];
	}

	private function getCount($gid)
	{
		$date = date('ymd', time());

		$model = ShareCountModel::where('create_date', $date)->where('gid', $gid)->find();

		if (!$model) {
			$count = 0;
		} else {
			$count = $model->count;
		}

		return $count;
	}

	private function addCount($gid)
	{
		$date = date('ymd', time());

		$model = ShareCountModel::where('create_date', $date)->where('gid', $gid)->find();

		if (!$model) {
			$model = new ShareCountModel();
			$model->save(['create_date' => $date, 'gid' => $gid, 'count' => 1]);
		} else {
			$model->save(['count' => ['inc', 1]]);
		}
	}

	/**
	 * 分享获取挑战机会
	 * @param $userId 用户id
	 * @param $shareType 分享类型
	 * @return integer
	 */
	private function shareGetChance($userId, $shareType)
	{
		$chanceNum = 0;
		$userRecord = UserRecordModel::where('user_id', $userId)->find();
		if (date('Y-m-d', $userRecord['last_share']) !== date('Y-m-d')) {
			$userRecord->share_user_num = 0;
			$userRecord->share_num = 0;
		}

		if ($shareType == 1) { //分享到个人
			if ($userRecord->share_user_num < ConfigService::get('share_user_get_chance_num_limit')) {
				$userRecord->share_user_num += 1;
				$userRecord->last_share = time();
				$chanceNum = ConfigService::get('share_get_chance_num');
				$userRecord->chance_num += $chanceNum;
				$userRecord->save();
			}
		} elseif ($shareType == 2) { // 分享到群
			if ($userRecord->share_num < ConfigService::get('share_get_chance_num_limit')) {
				$userRecord->share_num += 1;
				$userRecord->last_share = time();
				$chanceNum = ConfigService::get('share_get_chance_num');
				$userRecord->chance_num += $chanceNum;
				$userRecord->save();
			}
		}

		return $chanceNum;
	}

	/**
	 * 解密分享数据
	 * @param $userId
	 * @param $encryptedData
	 * @param $iv
	 * @see [加密数据解密算法](https://developers.weixin.qq.com/miniprogram/dev/api/signature.html#wxchecksessionobject)
	 * @return 
	 */
	public static function decryptedData($userId, $encryptedData, $iv, &$result)
	{
		$sessionKey = UserModel::get($userId)->session_key;
		if (strlen($sessionKey) != 24) {
			return self::$IllegalAesKey;
		}

		if (strlen($iv) != 24) {
			return self::$IllegalIv;
		}

		$aesKey = base64_decode($sessionKey);
		$aesIV = base64_decode($iv);
		$aesCipher = base64_decode($encryptedData);
		$result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
		$dataObj = json_decode($result);

		if ($dataObj == NULL) {
			return self::$IllegalBuffer;
		}

		if ($dataObj->watermark->appid != Config::get('wx_appid')) {
			return self::$IllegalBuffer;
		}

		return self::$OK;
	}
}