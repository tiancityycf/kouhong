<?php

namespace api_data_service\v2_0_2;

use service\NiuNiuGame;
use api_data_service\Config as ConfigService;
use model\UserRecord as UserRecordModel;
use model\UserLevel as UserLevelModel;

/**
 * 
 */
class NiuNiu 
{
	public function getNiuNiu($user_id)
	{
		$userRecord = UserRecordModel::where('user_id', $user_id)->find();

		$info = UserLevelModel::where('id', $userRecord->user_level)->find();

		$niniuService = new NiuNiuGame();

		$data = [];
		for ($i=0; $i < $info->total_num + 1; $i++) {
			//获取玩家的牌
			$wanjia = $niniuService->qupai();
			$data[$i]['wanjia']['pukepai'] = $wanjia['key_arr'];
			$data[$i]['wanjia']['niu'] = NiuNiuGame::JudgeCowCow($wanjia['value_arr']);

			//获取庄家的牌
			$zhuangjia = $this->qupai($wanjia['key_arr']);
			$data[$i]['zhuangjia']['pukepai'] = $zhuangjia['key_arr'];
			$data[$i]['zhuangjia']['niu'] = NiuNiuGame::JudgeCowCow($zhuangjia['value_arr']);

			//判断胜负
			if ($data[$i]['wanjia']['niu'] == $data[$i]['zhuangjia']['niu']) {
				if (max($wanjia['key_arr']) > max($zhuangjia['key_arr'])) {
					$data[$i]['shengfu'] = 1;   //1玩家胜  0和局  -1玩家负
				} else if (max($wanjia['key_arr']) == max($zhuangjia['key_arr'])) {
					$data[$i]['shengfu'] = 0;
				} else {
					$data[$i]['shengfu'] = -1;
				}
			} else if ($data[$i]['wanjia']['niu'] == 0 && $data[$i]['zhuangjia']['niu'] != 0) {
				$data[$i]['shengfu'] = 1;
			} else if ($data[$i]['wanjia']['niu'] != 0 && $data[$i]['zhuangjia']['niu'] == 0) {
				$data[$i]['shengfu'] = -1;
			} else if ($data[$i]['wanjia']['niu'] > 0 && $data[$i]['zhuangjia']['niu'] > 0 && $data[$i]['wanjia']['niu'] > $data[$i]['zhuangjia']['niu']) {
				$data[$i]['shengfu'] = 1;
			} else if ($data[$i]['wanjia']['niu'] > 0 && $data[$i]['zhuangjia']['niu'] > 0 && $data[$i]['wanjia']['niu'] < $data[$i]['zhuangjia']['niu']) {
				$data[$i]['shengfu'] = -1;
			} else if ($data[$i]['wanjia']['niu'] < 0 && $data[$i]['zhuangjia']['niu'] > 0) {
				$data[$i]['shengfu'] = -1;
			} else if ($data[$i]['wanjia']['niu'] > 0 && $data[$i]['zhuangjia']['niu'] < 0) {
				$data[$i]['shengfu'] = 1;
			} else {
				$data[$i]['shengfu'] = 0;
			}

		}

		//$data['total_num'] = $info->total_num;
		//$data['tongguan_num'] = $info->tongguan_num;

		return ['data' => $data, 'total_num' => $info->total_num, 'tongguan_num' => $info->tongguan_num];
	}

	//去掉5张牌，再获得5张牌
	public function qupai($arr)
	{
		$puke_arr = NiuNiuGame::pukepai();

		foreach ($arr as $key) {
			unset($puke_arr[$key]);
		}

		$key_arr = array_rand($puke_arr, 5);

        $data = [];
        foreach ($key_arr as $v) {
            $data[$v] = $puke_arr[$v];
        }

        $value_arr = array_values($data);

        return ['data' => $data, 'key_arr' => $key_arr, 'value_arr' => $value_arr];
	}
}