<?php

namespace app\khj\service\v1_0_1;

use app\khj\model\UserRecord as UserRecordModel;
use app\khj\model\SuccessLog as SuccessLogModel;
use app\khj\model\UserGoods as UserGoodsModel;


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

    public function user_goods_list($data)
    {
    	$user_goods_list = [];
    	$user_success_list = [];

    	$user_goods = UserGoodsModel::where('user_id', $data['user_id'])->select();

    	if ($user_goods) {
    		foreach ($user_goods as $key => $value) {
    			$user_goods_list[$key]['id'] = $value->id;
    			$user_goods_list[$key]['create_time'] = $value->create_time;
    			$user_goods_list[$key]['title'] = $value->goods->title;
    			$user_goods_list[$key]['img'] = $value->goods->img;
    			$user_goods_list[$key]['address'] = $value->address->region.' '.$value->address->addr;
    			$user_goods_list[$key]['is_shiping'] = $value->is_shiping;
    		}
    	}

    	$success_log = SuccessLogModel::where('user_id', $data['user_id'])->select();
    	if ($success_log) {
    		foreach ($success_log as $k => $v) {
    			$user_success_list[$key]['id'] = $v->id;
    			$user_success_list[$key]['win_time'] = $v->win_time;
    			$user_success_list[$key]['is_receive'] = $v->is_receive;
    			$user_success_list[$key]['title'] = $v->goods->title;
    			$user_success_list[$key]['img'] = $v->goods->img;
    		}
    	}


    	return ['user_goods_list' => $user_goods_list, 'user_success_list' => $user_success_list];

    }
}