<?php

namespace app\fzjx\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\v1_0_8\Prize as PrizeService;
use controller\BasicController;
use model\UserRecord as UserRecordModel;
use model\UserSuipian as UserSuipianModel;

/**
 * 奖品控制器类
 */
class Prize extends BasicController
{
	/**
	 * 奖品首页
	 * @return json
	 */
	public function index()
	{
		require_params('user_id');
		$userId = Request::param('user_id');
		$userSuipian = UserSuipianModel::where('user_id', $userId)->find();
		$prize_num_can_receive = $userSuipian->prize;

		$prizeService = new PrizeService();
		$prizeList = $prizeService->getPrizeList();
		$userPrizeList = $prizeService->getUserPrizeList($userId);

		return result(200, 'ok', ['prize_num_can_receive' => $prize_num_can_receive, 'prize_list' => $prizeList, 'user_prize_list' => $userPrizeList]);
	}

	/**
	 * 领取奖品
	 * @return json
	 */
	public function receive()
	{
		require_params('user_id', 'prize_id', 'name', 'phone', 'address');
		$data = Request::param();

		$prizeService = new PrizeService();
		$result = $prizeService->receive($data);

		return result(200, 'ok', $result);
	}
}