<?php

namespace app\khj\service\v1_0_1;

use app\khj\model\UserRecord as UserRecordModel;
use app\khj\model\SuccessLog as SuccessLogModel;
use app\khj\model\ChallengeLog as ChallengeLogModel;

//用户领取服务类
class UserGoods
{
	public function receive($data)
	{
		Db::startTrans();
        try {
			$successLog = SuccessLogModel::where('is_receive', 0)
				->where('goods_id', $data['goods_id'])
				->where('user_id', $data['user_id'])
				->order('id', 'asc')
				->find();

			if (!$successLog) {
				return ['status' => 0, 'msg' => '没有可领取的商品'];
			}

			$this->user_goods_log($data);

			$successLog->is_receive = 1;
			$successLog->save();

			Db::commit();
			return ['status' => 1];

		} catch (\Exception $e) {
            Db::rollback();
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
            'create_time' => $time,
        ]);
    }
}