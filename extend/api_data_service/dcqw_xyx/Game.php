<?php

namespace api_data_service\dcqw_xyx;

use think\Db;
use think\facade\Config;
use think\facade\Cache;
use model\User as UserModel;
use api_data_service\Config as ConfigService;
use api_data_service\Log as LogService;
use model\UserRecord as UserRecordModel;
use model\UserLevel as UserLevelModel;
use model\UserLevelWord as UserLevelWordModel;


/**
 * 
 */
class Game
{
	public function start($data)
	{
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

		if (!$this->canStart($userRecord)) {
			return ['status' => 0];
		}

		// 开启事务
		Db::startTrans();
		try {
			// 更新用户记录
			$userRecord->chance_num -= 1;
			$userRecord->challenge_num += 1;
			$userRecord->save();

			// 创建挑战日志
			$logService = new LogService();
			$challengeId = $logService->createChallengeLog($data);

			$game_config = $this->getUserLevel($data['user_id']);

			Db::commit();

			return ['status' => 1, 'challege_id' => $challengeId, 'game_config' => $game_config];
		} catch (\Exception $e) {
			Db::rollback();
			trace($e->getMessage(),'error');
			throw new \Exception($e->getMessage());
		}
	}


	public function end($data)
	{
		$result = ['status' => 0];
		if ($data['challenge_id'] == '' || $data['challenge_id'] <= 0) {
			trace($data,'error');
			return $result;
		}

        Db::startTrans();
		try {
	        $challengeLog = ChallengeLogModel::where('id', $data['challenge_id'])->where('user_id', $data['user_id'])->lock(true)->find();
	        if (!$challengeLog || $challengeLog['end_time'] != 0) {
	        	Db::commit();
	        	return $result;
	        }

	        $user_level = UserLevelModel::where('id', $userRecord->user_level)->find();
	        if ($user_level->score != $data['score']) {
	        	return $result;
	        }

	        $userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();
			if (isset($data['score']) && $data['score'] > $userRecord->highest_score) {
	            $userRecord->highest_score = $data['score'];
	        }

	        $new_user_level = UserLevelModel::where('id', $userRecord->user_level + 1)->find();

	        if (isset($data['successed']) && $data['successed']) {
	        	$userRecord->success_num += 1;

	            if ($new_user_level && $userRecord->success_num >= $new_user_level->success_num) {
	            	$userRecord->user_level += 1;
	            }

	            $result = ['status' => 1];
	        }

        
			$userRecord->save();

			$logService = new LogService();
			$logService->updateChallengeLog($data);
			Db::commit();

			return $result;
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
		return $userRecord->chance_num > 0 ? true : false;
	}

	public function getUserLevel($user_id)
	{
		$userRecord = UserRecordModel::where('user_id', $user_id)->find();

		$user_level = UserLevelModel::where('id', $userRecord->user_level)->find();
		$score = 0;
		if ($user_level) {
			$score = $user_level->score;
		}

		$user_level_word = UserLevelWordModel::where('user_level_id', $userRecord->user_level)->select();
		$arr = [];
		if ($user_level_word) {
			foreach ($user_level_word as $key => $value) {
				$arr[$key]['score_mark'] = $value->score_mark;
				$arr[$key]['max'] = $value->max;
				$arr[$key]['time'] = $value->time;
			}
		}

		return ['score' => $score, 'config' => $arr];
		
	}
	
}