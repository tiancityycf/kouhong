<?php

namespace app\khj2\service\v1_0_1;

use app\khj2\model\GameRecord as GameRecordModel;
use app\khj2\model\ChallengeLog as ChallengeLogModel;
use app\khj2\model\UserRecord as UserRecordModel;
use app\khj2\model\SuccessLog as SuccessLogModel;
use app\khj2\model\Goods as GoodsModel;
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
                trace("余额不足 goods_id=".$data['goods_id'].' user_id='.$data['user_id'],'error');
                return ['status' => 0, 'msg' => '余额不足'];
            }

            $userRecord->money = ['dec',$goods->price];
            $userRecord->challenge_num = ['inc', 1];
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
        $trade_no = date("YmdHis").rand(100000,999999);
        $sdata = [
            'user_id' => $data['user_id'],
            'goods_id' => $data['goods_id'],
            'trade_no' => $trade_no,
            'start_time' => $time,
            'create_time' => $time,
        ];
        $challenge = ChallengeLogModel::create($sdata);

        return $challenge->id;
    }

    //更新挑战记录
    private function update_log($data)
    {
        $time = time();
        ChallengeLogModel::where('id', $data['challenge_id'])->update([
            'score' => isset($data['score']) ? $data['score'] : 0,
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
        // 开启事务
        Db::startTrans();
        try {
            $challengeLog = ChallengeLogModel::where('id', $data['challenge_id'])
                ->lock(true)
                ->find();
            if (!$challengeLog || $challengeLog['end_time'] != 0 || $challengeLog['user_id'] != $data['user_id'] || $challengeLog['goods_id'] != $data['goods_id']) {
                Db::rollback();
                trace("状态异常 challenge_id=".$data['challenge_id'],'error');
                return ['status' => 0,'msg' => '状态异常'];
            }

            $userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

            if (isset($data['is_win']) && $data['is_win']) {
                $userRecord->success_num = ['inc', 1];
                $userRecord->save();
            }

            $this->update_log($data);

            Db::commit();
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];
        } catch (\Exception $e) {
            Db::rollback();
            lg($e);
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 挑战记录
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function challenge_log($data)
    {
        try {
            $user_id = intval($data['user_id']);
//            $result = ChallengeLogModel::where('a.user_id',$data['user_id'])->order("id desc")->select();
            $result = Db::query("select a.*,b.title,b.img,c.status,d.cate_name from t_challenge_log a left join t_goods b on a.goods_id=b.id left join t_good_cates d on b.cate=d.id left join t_user_goods c on a.id=c.challenge_id where a.user_id={$user_id} order by a.id desc");
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            lg($e);
            throw new \Exception($e->getMessage());
        }
    }

}
