<?php

namespace app\hzdlt\controller\api\v1_0_5;

use think\facade\Request;

use api_data_service\v2_0_1_2\Index as IndexService;
use api_data_service\v2_0_1_2\Redpacket as RedpacketService;
use controller\BasicController;

/**
 * 首页控制器类
 */
class Index extends BasicController
{
    /**
     * 小程序首页
     * @return json
     */
    public function index()
    {
        require_params('user_id');
        $userId = Request::param('user_id');
        $version = Request::param('version') ? Request::param('version') : '';

        $indexService = new IndexService();
        $fakerList = $indexService->getFakerWinPrizeList();
        $indexInfo = $indexService->getIndexInfo($userId, $version);

        return result(200, 'ok', [
            'faker_list' => $fakerList,
            'index_info' => $indexInfo,
        ]);
    }

    /**
     * 排行榜
     * @return json
     */
    public function top()
    {
        $indexService = new IndexService();
        $wealthList = $indexService->getSuccessList();
        $willList = $indexService->getWillList();

        return result(200, 'ok', [
            'wealth_list' => $wealthList,
            'will_list' => $willList,
        ]);
    }

    /**
     * 用户须知
     * @return json
     */
    public function readme()
    {
        $indexService = new IndexService();
        $result = $indexService->getReadme();

        return result(200, 'ok', $result);
    }

    public function check()
    {
        require_params('user_id');
        $data = Request::param();

        $indexService = new IndexService();
        $result = $indexService->check($data);

        return result(200, 'ok', $result);
    }


    public function share()
    {
        require_params('user_id', 'encryptedData', 'iv', 'redpacket_id');
        $data = Request::param();

        $redpacketService = new RedpacketService();
        $result = $redpacketService->share($data);

        return result(200, 'ok', $result);
    }
}
