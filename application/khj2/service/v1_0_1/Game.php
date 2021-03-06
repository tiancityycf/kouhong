<?php

namespace app\khj2\service\v1_0_1;

use app\khj2\model\GameRecord as GameRecordModel;
use app\khj2\model\ChallengeLog as ChallengeLogModel;
use app\khj2\model\User as UserModel;
use app\khj2\model\Sign as SignModel;
use app\khj2\model\UserLipstick as UserLipstickModel;
use app\khj2\model\Goods as GoodsModel;
use think\Db;

/**
 * 游戏服务类
 */
class Game
{

	//创建挑战记录
	private function create_log($data)
	{
		$time = time();
		$trade_no = date("YmdHis").rand(100000,999999);
		$sdata = [
			'user_id' => $data['user_id'],
			'successed' => $data['success'],
			'goods_id' => 0,
			'trade_no' => $trade_no,
			'start_time' => $time,
			'create_time' => $time,
			];
		$challenge = ChallengeLogModel::create($sdata);

		if($data['success']==1){
			$sdata = [
				'user_id' => $data['user_id'],
				'challenge_id' => $challenge->id,
				'invite_id' => 0,
				'status' => 0,
				'create_time' => $time,
				];
			$lipstick = UserLipstickModel::create($sdata);

			$lipstick_id = $lipstick->id;
			$user = UserModel::where('id', $data['user_id'])->find();
			if($user['invite_id']>0){
				$sdata = [
					'user_id' => $user['invite_id'],
					'challenge_id' => $challenge->id,
					'invited_id' => $data['user_id'],
					'status' => 0,
					'create_time' => $time,
					];
				$lipstick = UserLipstickModel::create($sdata);
			}
		}else{
			$where = [];
			$where['user_id'] = $data['user_id'];
			$where['status'] = 0;
			$lipstick_id = UserLipstickModel::where($where)->count();
		}

		
		$result = [];
		$m = UserModel::where('id', $data['user_id'])->find();
		if($m['sign_type']>0 && $m['sign_result']==0 && $m['sign_days']<180){
			$start = strtotime(date('Y-m-d').' 00:00:00');
			$end = strtotime(date('Y-m-d').' 23:59:59');
			$c = ChallengeLogModel::where('user_id',$data['user_id'])->where("start_time","between",[$start,$end])->count();
			if($c>2){
				$dday = date('ymd');
				$sm = SignModel::where('user_id', $data['user_id'])->where('dday',$dday)->find();
				if(empty($sm)){
					$sm = new SignModel();
					$sm->ext = 0;
					$sm->linked = 1;
					$sm->user_id = $data['user_id'];
					$sm->dday = $dday;
					$sm->addtime = time();
					$sm->save();
					$m->sign_days = $m->sign_days + 1;
					$m->save();
					$result['msg'] = '今日打卡完成';
				}
			}
		}

		$result['lipstick_id'] = $lipstick_id;
		$result['msg'] = $lipstick_id;
		return $result;
	}

	/**
	 * 游戏结束
	 * @param  [type] $data 接收参数
	 * @return [type]       [description]
	 */
	public function end($data)
	{
		try {
			$result = $this->create_log($data);
			if($result['lipstick_id']>0){
				$has_reward = 1;
			}else{
				$has_reward = 0;
			}
			return [
				'has_reward' => $has_reward,
				'msg' => $result['msg'],
				];
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}
	//查看是否有未领取的口红
	public function lipstick($data)
	{
		$where = [];
		$where['user_id'] = $data['user_id'];
		$where['status'] = 0;
		$id = UserLipstickModel::where($where)->count();
		if($id>0){
			$has_reward = 1;
		}else{
			$has_reward = 0;
		}
		return ['has_reward' => $has_reward];
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
