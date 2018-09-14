<?php

namespace app\szqyh\controller\api\v1_0_4;

use think\facade\Request;
use api_data_service\v1_0_9_1\Pifu as PifuService;
use controller\BasicController;

/**
 * 小程序皮肤控制器类
 */
class Pifu extends BasicController
{
	/**
     * 皮肤商店列表
     * @return json
     */
    public function pifu_list()
    {
        require_params('user_id');
        $data = Request::param();

        $pifuService = new PifuService();
        $result = $pifuService->pifu_list($data);

        return result(200, 'ok', $result);
    }

    //购买皮肤
    public function buy()
    {
    	require_params('user_id', 'pifu_id');
        $data = Request::param();

        $pifuService = new PifuService();
        $result = $pifuService->buyPifu($data);

        return result(200, 'ok', $result);
    }

    //选择当前皮肤
    public function pifu_select()
    {
    	require_params('user_id', 'pifu_id');
        $data = Request::param();

        $pifuService = new PifuService();
        $result = $pifuService->selectPifu($data);

        return result(200, 'ok', $result);
    }
    
    public function pifu_share()
    {
        require_params('user_id', 'pifu_id', 'encryptedData', 'iv');
        $data = Request::param();

        $pifuService = new PifuService();
        $result = $pifuService->sharePifu($data);

        return result(200, 'ok', $result);
    }

    public function pifu_info()
    {
        require_params('user_id', 'pifu_id');
        $data = Request::param();

        $pifuService = new PifuService();
        $result = $pifuService->pifuIfo($data);

        return result(200, 'ok', $result);
    }
}