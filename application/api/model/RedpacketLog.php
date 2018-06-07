<?php

namespace app\api\model;

use think\Model;

/**
 * 红包日志模型类
 */
class RedpacketLog extends Model
{
    /**
     * 获取红包记录
     * @param  integer $userId 用户id
     * @return array
     */
    public function getRedpacketList($userId)
    {
        $receiveList = self::where('user_id', $userId)->select();
        $result = [];
        foreach ($receiveList as $key => $receive) {
            $result[$key] = [
                'amount' => $receive['amount'],
                'create_time' => $receive['create_time'],
            ];
        }

        return $result;
    }
}