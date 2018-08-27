<?php

namespace api_data_service\v1_0_8;

use think\Db;
use think\facade\Cache;
use model\Prize as PrizeModel;
use api_data_service\Config as ConfigService;
use model\UserPrize as UserPrizeModel;
use model\UserRecord as UserRecordModel;
use model\UserSuipian as UserSuipianModel;

class Prize
{
	/**
     * 获取奖品列表
     * @return array
     */
    public function getPrizeList()
    {
    	// 如果缓存没有，则去数据库获取
    	$cacheKey = config('prize_key');
    	if (Cache::has($cacheKey)) {
    		return Cache::get($cacheKey);
    	} else {
    		$prizeModel = new PrizeModel();
    		$list = $prizeModel->getPrizeList();
    		$expire = ConfigService::get('honor_refresh_time') * 60;
    		Cache::set($cacheKey, $list, $expire);

    		return $list;
    	}
    }

    /**
     * 获取用户领奖记录
     * @param  $userId 用户id
     * @return array
     */
    public function getUserPrizeList($userId)
    {
        $userPrizeModel = new UserPrizeModel();
        return $userPrizeModel->getUserPrizeList($userId);
    }

    /**
     * 领奖
     * @param  $data 请求数据
     * @return boolean
     */
    public function receive($data)
    {
        $userSuipian = UserSuipianModel::where('user_id', $data['user_id'])->find();

        if (!$this->canReceive($userSuipian)) {
            return ['status' => 0];
        }

        // 开启事务
        Db::startTrans();
        try {
            // 创建用户领取奖品记录
            UserPrizeModel::create([
                'user_id' => $data['user_id'],
                'prize_id' => $data['prize_id'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'create_time' => time(),
            ]);

            // 更新用户信息
            $userSuipian->prize -= 1;
            $userSuipian->save();

            Db::commit();
            
            return ['status' => 1];
        } catch (\Exception $e) {
             Db::rollback();
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 判断是否可以领奖
     * @param  $userRecord
     * @return boolean
     */
    private function canReceive($userSuipian)
    {
        return $userSuipian->prize;
    }
}