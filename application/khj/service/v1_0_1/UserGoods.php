<?php

namespace app\khj\service\v1_0_1;

use app\khj\model\UserGoods as UserGoodsModel;
use app\khj\model\ChallengeLog as ChallengeLogModel;
use think\Db;

//用户领取服务类
class UserGoods
{
	public function receive($data)
	{
		Db::startTrans();
        try {
			$log = ChallengeLogModel::where('id', $data['challenge_id'])
				->find();

			if ($log['successed']!=1) {
				return ['status' => 0, 'msg' => '没有挑战成功'];
			}
            if ($log['user_id']!=$data['user_id']) {
                return ['status' => 0, 'msg' => '非法操作'];
            }

            $exist = UserGoodsModel::where("challenge_id",$data['challenge_id'])->find();
            if (!empty($exist)) {
                return ['status' => 0, 'msg' => '已领取'];
            }

			$this->user_goods_log($data);
			Db::commit();
			return ['status' => 1];

		} catch (\Exception $e) {
            Db::rollback();
            lg($e);
            throw new \Exception($e->getMessage());
        }
	}

	//创建成功记录
    private function user_goods_log($data)
    {
        $time = time();
        UserGoodsModel::create([
            'user_id' => $data['user_id'],
            'goods_id' => $data['goods_id'],
            'address_id' => $data['address_id'],
            'challenge_id' => $data['challenge_id'],
            'create_time' => $time,
        ]);
    }
}