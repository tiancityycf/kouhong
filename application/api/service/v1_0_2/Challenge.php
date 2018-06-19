<?php

namespace app\api\service\v1_0_2;

use think\Db;

use app\api\service\Log as LogService;
use app\api\service\Config as ConfigService;
use app\api\model\UserRecord as UserRecordModel;
use app\api\model\ChallengeLog as ChallengeLogModel;
use app\api\service\v1_0_2\Word as WordService;
use app\api\service\v1_0_2\Redpacket as RedpacketService;


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
		if (!ConfigService::get('open_challenge_unlimit')) {
			if (!$this->canStart($userRecord)) {
				return ['status' => 0];
			}
		}

		// 开启事务
		Db::startTrans();
		try {
			// 更新用户记录
			if (!ConfigService::get('open_challenge_unlimit')) {
				$userRecord->chance_num -= 1;
			}
			$userRecord->challenge_num += 1;
			$userRecord->save();

			// 创建挑战日志
			$logService = new LogService();
			$challengeId = $logService->createChallengeLog($data);

			// 获取词语列表
			$wordService = new WordService();
			$words = $wordService->getRandWords($data['user_id']);

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
		$result = ['status' => 0];
		$userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();
		if (isset($data['score']) && $data['score'] > $userRecord->highest_score) {
            $userRecord->highest_score = $data['score'];
        }

        $challengeLog = ChallengeLogModel::get($data['challenge_id']);
        if ($challengeLog['end_time'] != 0) {
        	return ['status' => 0];
        }

        if (isset($data['successed']) && $data['successed']) {
            $userRecord->success_num += 1;
            $nextLevel = $userRecord->user_level + 1;
            if ($userRecord->success_num == ConfigService::get('user_level_' . $nextLevel . '_success_num')) {
                $userRecord->user_level += 1;
            }

            $result = ['status' => 1, 'amount' => RedpacketService::randOne($data['user_id'])];
        }

        // 开启事务
		Db::startTrans();
		try {
			$userRecord->save();

			$logService = new LogService();
			$logService->updateChallengeLog($data);
			Db::commit();

			return $result;
		} catch (\Exception $e) {
			Db::rollback();
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
}