<?php
namespace api_data_service\v2_0_2;

use think\Db;
use api_data_service\Config as ConfigService;
use api_data_service\Share as ShareService;
use model\UserRecord as UserRecordModel;
use model\RedpacketLog as RedpacketLogModel;
use model\ShareRedpacket as ShareRedpacketModel;
use model\UserLevel as UserLevelModel;

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
		
		//$share_count = ShareRedpacketModel::where('user_id', $userId)->where('create_date', date('ymd', time()))->count();
		//$share_limit = ConfigService::get('share_get_chance_num_limit');

		$is_limit_status = self::getLimit($userId);

		$redpacket_id = 0;
		$now_amount = 0;
		$is_free = 0;
		if ($is_limit_status) {
			$userRecord = UserRecordModel::where('user_id', $userId)->find();
			$user_level = UserLevelModel::where('id', $userRecord->user_level)->find();
			$min = $user_level->amount_min;
			$max = $user_level->amount_max;
			$amount = rand($min * 100, $max * 100) / 100;

			
			$model = new RedpacketLogModel();
			$model->save([
				'user_id' => $userId,
				'amount' => $amount,
				'create_time' => time(),
				'create_date' => date('ymd', time()),
			]);

			$count = RedpacketLogModel::where('user_id', $userId)->where('create_date', date('ymd', time()))->where('status', 1)->count();
			if ($count < ConfigService::get('login_get_chance_num')) {
				//$userRecord = UserRecordModel::where('user_id', $userId)->find();
				$userRecord->amount += $amount;
				$userRecord->amount_total += $amount;
				$userRecord->redpacket_num += 1;
				$userRecord->save();

				$level = self::checkLevel($userRecord->redpacket_num);
				$userRecord->user_level = $level;
				$userRecord->save();

				$model->status = 1;
				$model->save();

				$now_amount = $amount;
				$is_free = 1;
			}

			$redpacket_id = $model->id;
			
		}
		

		return ['redpacket_id' =>$redpacket_id, 'now_amount' => $now_amount, 'is_free' => $is_free];
	}


	public function share($data)
	{
		$time = time();
		$date = date('ymd', time());

		$redpacketLog = RedpacketLogModel::where('id', $data['redpacket_id'])->where('create_date',$date)->where('user_id', $data['user_id'])->find();
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

		$errorCode = ShareService::decryptedData($data['user_id'], $data['encryptedData'], $data['iv'], $result);

		$status = 0;   //接口状态 0 失败  1 成功
		$is_limit = 0; //  分享达上限  0 达上限  1 未达上限
		$is_open = 0;  //  红包是否打开   0未打开  1 打开
		$is_new_group = 0; // 是否分享到重复群  0 重复群  1 未重复
		$amount = 0;  //   红包金额
		if ($errorCode == 0 && $redpacketLog) {
			$resultArr = json_decode($result, true);
			$group_id = $resultArr['openGId'];

			$is_limit_status = self::getLimit($data['user_id']);

			if ($is_limit_status) {
				$old_share_log = ShareRedpacketModel::where('user_id', $data['user_id'])
					->where('gid', $group_id)
					->where('create_date', $date)
					->find();

				if (!$old_share_log) {
					Db::startTrans();
					try{
						$share_log = new ShareRedpacketModel();
						$share_log->user_id = $data['user_id'];
						$share_log->gid = $group_id;
						$share_log->create_time = $time;
						$share_log->create_date = $date;

						$share_log->save();

						
						$userRecord->amount += $redpacketLog->amount;
						$userRecord->amount_total += $redpacketLog->amount;
						$userRecord->redpacket_num += 1;
						$userRecord->save();

						$redpacketLog->status = 1;
						$redpacketLog->save();

						$level = self::checkLevel($userRecord->redpacket_num);
						$userRecord->user_level = $level;
						$userRecord->save();

						Db::commit();
						$is_open = 1;
						$amount = $redpacketLog->amount;
						
					} catch (\Exception $e) {
						Db::rollback();
						trace($e->getMessage(),'error');
						throw new \Exception("系统繁忙");
					}
					$is_new_group = 1; 
				}
				$is_limit = 1;
			}
			$status = 1;
		}

		$first_withdraw_success_num = ConfigService::get('first_withdraw_success_num');
    	$first_withdraw_limit = ConfigService::get('first_withdraw_limit');
    	$withdraw_limit = $userRecord->redpacket_num > $first_withdraw_success_num ? ConfigService::get('withdraw_limit') : $first_withdraw_limit;

		return [
			'status' => $status,
			'is_limit' => $is_limit,
			'is_new_group' => $is_new_group,
			'is_open' => $is_open,
			'amount' => $amount,
			'withdraw_limit' => $withdraw_limit,
			'success_num' => $userRecord->redpacket_num,
			'user_amount' => $userRecord->amount,
		];
	}

	//判断是否分享达上限
	public static function getLimit($user_id)
	{
		$share_count = ShareRedpacketModel::where('user_id', $user_id)->where('create_date', date('ymd', time()))->count();
		$share_limit = ConfigService::get('share_get_chance_num_limit');

		if ($share_count < $share_limit) {
			return true;
		} else {
			return false;
		}
	}

	public static function checkLevel($num)
	{
		$user_level = UserLevelModel::where('success_num', '<=',$num)->order('id desc')->find();
        $user_level_max = UserLevelModel::order('id desc')->find()->id;

        if ($user_level) {
        	$level = $user_level->id;
        } else {
        	$level = $user_level_max;
        }

        return $level;
	}
}