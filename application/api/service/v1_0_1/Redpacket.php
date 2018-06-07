<?php
namespace app\api\service\v1_0_1;

use think\Db;
use app\api\service\Config as ConfigService;
use app\api\model\UserRecord as UserRecordModel;
use app\api\model\RedpacketLog as RedpacketLogModel;

/**
 * 红包服务类
 */
class Redpacket
{
	/**
	 * 生成一个随机红包
	 * @param  integer $userId 用户id
	 * @return float
	 */
	public static function randOne($userId)
	{
		list($min, $max) = ConfigService::get('redpacket_range');
		$amount = rand($min * 100, $max * 100) / 100;

		// 开启事务
		Db::startTrans();
		try {
			$userRecord = UserRecordModel::where('user_id', $userId)->find();
			$userRecord->amount += $amount;
			$userRecord->amount_total += $amount;
			$userRecord->save();

			RedpacketLogModel::create([
				'user_id' => $userId,
				'amount' => $amount,
				'create_time' => time(),
			]);

			Db::commit();

			return $amount;
		} catch (\Exception $e) {
			Db::rollback();
			throw new \Exception('系统繁忙');
		}
	}

}