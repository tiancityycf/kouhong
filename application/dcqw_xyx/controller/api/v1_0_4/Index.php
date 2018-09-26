<?php

namespace app\dcqw_xyx\controller\api\v1_0_4;

use think\facade\Request;

use api_data_service\dcqw_xyx\Index as IndexService;
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

        $indexService = new IndexService();
        $indexInfo = $indexService->getIndexInfo($userId);

        return result(200, 'ok', [
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
}
