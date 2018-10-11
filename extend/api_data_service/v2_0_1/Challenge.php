<?php

namespace api_data_service\v2_0_1;

use think\Db;

use api_data_service\Log as LogService;
use api_data_service\Config as ConfigService;

use model\UserRecord as UserRecordModel;
use model\ChallengeLog as ChallengeLogModel;
use model\UserLevel as UserLevelModel;
use model\ShareRedpacket as ShareRedpacketModel;

use api_data_service\Word as WordService;
use api_data_service\v2_0_1\Redpacket as RedpacketService;


/**
 * 游戏挑战服务类
 */
class Challenge
{
	/**
	 * 游戏挑战开始
	 * @param  $data 请求数据
	 * @return array
	 */
	public function start($data)
	{
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();
		/*if (!ConfigService::get('open_challenge_unlimit')) {
			if (!$this->canStart($userRecord)) {
				return ['status' => 0];
			}
		}*/

		$heimingdan_config = ConfigService::get('heimingdan_in_off');
        $zongheimingdan_config = config('heimingdan_zongkaiguan');

        if ($zongheimingdan_config == 1 && $heimingdan_config == 1) {
            $user_status = $userRecord->user_status;
        } else {
            $user_status = 1;
        }

        if ($userRecord->user_status == 2) {
        	$user_status = 0;
        }

		// 开启事务
		Db::startTrans();
		try {
			// 更新用户记录
			/*if (!ConfigService::get('open_challenge_unlimit') && $user_status == 1) {
				$userRecord->chance_num -= 1;
			}*/
			$userRecord->challenge_num += 1;
			$userRecord->save();

			// 创建挑战日志
			$logService = new LogService();
			$challengeId = $logService->createChallengeLog($data);

			// 获取词语列表
			$wordService = new WordService();
			$words = $wordService->getWords($data['user_id']);

			Db::commit();

			return ['status' => 1, 'challege_id' => $challengeId, 'words' => $words];
		} catch (\Exception $e) {
			Db::rollback();
			throw new \Exception($e->getMessage());
		}
	}

	/**
	 * 游戏挑战结束
	 * @param  $data 请求数据
	 * @return integer
	 */
	public function end($data)
	{
		$is_free = 0;
		$is_limit = 0;
		$result = ['status' => 0];
		if ($data['challenge_id'] == '') {
			trace($data,'error');
			return ['status' => 0];
		}
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();
		if (isset($data['score']) && $data['score'] > $userRecord->highest_score) {
            $userRecord->highest_score = $data['score'];
        }

        // 开启事务
		Db::startTrans();
		try {
	        $challengeLog = ChallengeLogModel::where('id', $data['challenge_id'])->where('user_id', $data['user_id'])->lock(true)->find();
	        if (!$challengeLog || $challengeLog['end_time'] != 0) {
	        	Db::commit();
	        	return ['status' => 0];
	        }

	        $user_level = UserLevelModel::where('id', $userRecord->user_level + 1)->find();

	        if (isset($data['successed']) && $data['successed']) {
	            $userRecord->success_num += 1;
	            /*$nextLevel = $userRecord->user_level + 1;
	            if ($userRecord->success_num == ConfigService::get('user_level_' . $nextLevel . '_success_num')) {
	                $userRecord->user_level += 1;
	            }*/

	            if ($user_level && $userRecord->redpacket_num >= $user_level->success_num) {
	            	$userRecord->user_level += 1;
	            }

	            $heimingdan_config = ConfigService::get('heimingdan_in_off');
        		$zongheimingdan_config = config('heimingdan_zongkaiguan');
	            if ($zongheimingdan_config == 1 && $heimingdan_config == 1) {
		            $user_status = $userRecord->user_status;
		        } else {
		            $user_status = 1;
		        }

		        if ($userRecord->user_status == 2) {
		        	$user_status = 0;
		        }

				if ($user_status) {
					$arr = RedpacketService::randOne($data['user_id']);
					$redpacket_id = $arr['redpacket_id'];
					$amount = $arr['now_amount'];
					$is_free = $arr['is_free'];
				} else {
					$redpacket_id = 0;
					$amount = 0;
					$is_free = 0;
				}

				$is_limit_status = RedpacketService::getLimit($data['user_id']);
				if ($is_limit_status) {
					$is_limit = 1;
				}

	            $result = [
	            	'status' => 1,
	            	'redpacket_id' => $redpacket_id,
	            	'amount' => $amount,
	            	'is_limit' => $is_limit,
	            	'is_free' => $is_free,
	            	'user_status' => $user_status,
	            ];
	        }

        
			$userRecord->save();

			$logService = new LogService();
			$logService->updateChallengeLog($data);
			Db::commit();
			if ($is_free) {
				$first_withdraw_success_num = ConfigService::get('first_withdraw_success_num');
		    	$first_withdraw_limit = ConfigService::get('first_withdraw_limit');
		    	$withdraw_limit = $userRecord->redpacket_num > $first_withdraw_success_num ? ConfigService::get('withdraw_limit') : $first_withdraw_limit;

		    	$other_result = [
		    		'withdraw_limit' => $withdraw_limit,
					'success_num' => $userRecord->redpacket_num,
					'user_amount' => $userRecord->amount + (isset($result['amount']) ? $result['amount'] : 0),
		    	];
			} else {
				$other_result = [];
			}

			return $result + $other_result;
		} catch (\Exception $e) {
			Db::rollback();
			trace($e->getMessage(),'error');
			throw new \Exception("系统繁忙");
		} 
	}

	/**
	 * 判断是否可以开始游戏
	 * @param  $userRecord 用户记录
	 * @return boolean
	 */
	private function canStart($userRecord)
	{
		$heimingdan_config = ConfigService::get('heimingdan_in_off');
        $zongheimingdan_config = config('heimingdan_zongkaiguan');

		if ($zongheimingdan_config == 1 && $heimingdan_config == 1) {
            $user_status = $userRecord->user_status;
        } else {
            $user_status = 1;
        }

        if ($userRecord->user_status == 2) {
        	$user_status = 0;
        }
		return ($userRecord->chance_num > 0 || $user_status == 0) ? true : false;
	}

	public function lianxi($data)
	{
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

		$userRecord->lianxi_num += 1;
		$userRecord->save();

		// 获取词语列表
		$wordService = new WordService();
		$words = $wordService->lianxi();

		return ['status' => 1, 'words' => $words, 'challege_id' => 0,];
	}
}