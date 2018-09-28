<?php

namespace api_data_service\v2_0_1_3;

use think\Db;

use api_data_service\Log as LogService;
use api_data_service\Config as ConfigService;

use model\UserRecord as UserRecordModel;
use model\ChallengeLog as ChallengeLogModel;
use model\UserLevel as UserLevelModel;
use model\ShareRedpacket as ShareRedpacketModel;

use api_data_service\v2_0_1_3\Word as WordService;
use api_data_service\v2_0_1_3\Redpacket as RedpacketService;
use api_data_service\v2_0_1_3\User as UserService;


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
		$version = isset($data['version']) ? $data['version'] : '';
		$user_status = $this->checkUserStatus($userRecord, $version);
		
		if (!$this->canStart($userRecord, $user_status)) {
			return ['status' => 0];
		}

		// 开启事务
		Db::startTrans();
		try {
			// 更新用户记录
			if ($user_status == 1) {
				$userRecord->chance_num -= 1;
			}
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

	            if ($user_level && $userRecord->success_num >= $user_level->success_num) {
	            	$userRecord->user_level += 1;
	            }

	            $version = isset($data['version']) ? $data['version'] : '';

	            $user_status = $this->checkUserStatus($userRecord, $version);

	            $result = [
	            	'status' => 1,
	            	'amount' => $user_status ? RedpacketService::randOne($data['user_id']) : 0,
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
	private function canStart($userRecord, $user_status)
	{
		return ($userRecord->chance_num > 0 || $user_status == 0) ? true : false;
	}

	public function chekVersion($version)
    {
        $userService = new UserService();
        return $userService->chekVersion($version);
    }


    public function checkUserStatus($userRecord, $version)
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

        if ($this->chekVersion($version)) {
            $user_status = 0;
        }

        return $user_status;
    }
}