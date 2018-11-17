<?php

namespace app\khj\service\v1_0_1;

use app\khj\model\GameRecord as GameRecordModel;
use app\khj\model\ChallengeLog as ChallengeLogModel;
use app\khj\model\UserRecord as UserRecordModel;
use app\khj\model\SuccessLog as SuccessLogModel;
use app\khj\model\Goods as GoodsModel;
use think\Db;

/**
 * 游戏服务类
 */
class Game
{
    protected $configData;

    public function __construct($configData = [])
    {
        $this->configData = $configData;
    }

    //游戏开始
    public function start($data)
    {
        Db::startTrans();
        try {
            $goods = GoodsModel::where('id', $data['goods_id'])->find();
            if (empty($goods)) {
                trace("商品不存在".$data['goods_id'],'error');
                return ['status' => 0, 'msg' => '商品不存在'];
            }
            $userRecord = UserRecordModel::where('user_id', $data['user_id'])->lock(true)->find();

            if ($userRecord->money < $goods->price) {
                Db::rollback();
                return ['status' => 0, 'msg' => '余额不足'];
            }

            $userRecord->money -= $goods->price;
            $userRecord->challenge_num += 1;
            $userRecord->save();
            $challenge_id = $this->create_log($data);
            Db::commit();
            return ['status' => 1, 'challenge_id' => $challenge_id, 'msg' => 'ok'];

        } catch (\Exception $e) {
            Db::rollback();
            lg($e);
            throw new \Exception($e->getMessage());
        }
    }


    //创建挑战记录
    private function create_log($data)
    {
        $time = time();
        $challenge = ChallengeLog::create([
            'user_id' => $data['user_id'],
            'goods_id' => $data['goods_id'],
            'start_time' => $time,
            'create_time' => $time,
        ]);

        return $challenge->id;
    }

    //创建成功记录
    private function success_log($data)
    {
        $time = time();
        $date = date('ymd', $time);

        SuccessLogModel::create([
            'user_id' => $data['user_id'],
            'goods_id' => $data['goods_id'],
            'challenge_id' => $data['challenge_id'],
            'win_time' => $time,
            'win_date' => $date,
        ]);
    }

    //更新挑战记录
    private function update_log($data)
    {
        $time = time();
        ChallengeLog::where('id', $data['challenge_id'])->update([
            'score' => isset($data['checkpoint']) ? $data['checkpoint'] : 0,
            'successed' => isset($data['is_win']) ? $data['is_win'] : 0,
            'end_time' => $time,
            'update_time' => $time,
        ]);
    }

    /**
     * 游戏结束
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function end($data)
    {   
        if ($data['challenge_id'] == '') {
            return ['status' => 0];
        }
        // 开启事务
        Db::startTrans();
        try {
            $challengeLog = ChallengeLogModel::where('id', $data['challenge_id'])
                ->where('user_id', $data['user_id'])
                ->where('goods_id',$data['goods_id'])
                ->lock(true)
                ->find();
            if (!$challengeLog || $challengeLog['end_time'] != 0) {
                return ['status' => 0];
            }

            if (isset($data['is_win']) && $data['is_win']) {
                $userRecord->success_num += 1;

                $this->success_log($data);
                
            }
            $userRecord->save();
            
            $this->update_log($data);

            Db::commit();
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }
}
