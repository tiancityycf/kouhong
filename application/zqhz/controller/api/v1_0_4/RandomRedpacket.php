<?php

namespace app\zqhz\controller\api\v1_0_4;

use api_data_service\v1_0_7\RandomRedpacket as RandomRedpacketService;
use controller\BasicController;
use think\facade\Request;

/**
 * 随机红包制器类
 */
class RandomRedpacket extends BasicController
{
	/**
	 * 点击
	 * @return json
	 */
	public function click()
	{
		require_params('share_user_id', 'random_redpacket_id', 'click_user_id', 'encryptedData', 'iv');
		$data = Request::param();

		$randomRedpacket = new RandomRedpacketService();
		$result = $randomRedpacket->click($data);

		return result(200, '0k', $result);
	}

	public function check()
	{
		require_params('share_user_id', 'random_redpacket_id');
		$data = Request::param();

		$randomRedpacket = new RandomRedpacketService();
		$result = $randomRedpacket->check($data);

		return result(200, '0k', $result);
	}

	public function txt()
	{

		$randomRedpacket = new RandomRedpacketService();
		$result = $randomRedpacket->txt();

		return result(200, '0k', $result);
	}

	public function amount()
	{
		require_params('share_user_id', 'random_redpacket_id');
		$data = Request::param();

		$randomRedpacket = new RandomRedpacketService();
		$result = $randomRedpacket->amount($data);

		return result(200, '0k', $result);
	}
}