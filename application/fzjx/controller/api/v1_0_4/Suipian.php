<?php

namespace app\fzjx\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\v1_0_8\UserSuipian as UserSuipianService;
use controller\BasicController;


class Suipian extends BasicController
{

	public function tili()
	{
		require_params('user_id');
        $data = Request::param();

        $suipian = new UserSuipianService();
        $result = $suipian->tili($data);

        return result(200, 'ok', $result);
	}

	public function hecheng()
	{
		require_params('user_id');
        $data = Request::param();

        $suipian = new UserSuipianService();
        $result = $suipian->hecheng($data);

        return result(200, 'ok', $result);
	}
}