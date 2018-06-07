<?php

namespace app\api\controller\v1_0_1;

use think\facade\Request;

use app\api\service\v1_0_1\Index as IndexService;
use app\api\controller\v1_0_1\BasicController;

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
        $fakerList = $indexService->getFakerWinPrizeList();
        $indexInfo = $indexService->getIndexInfo($userId);

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
        $wealthList = $indexService->getWealthList();
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
        $readme = $indexService->getReadme();

        return result(200, 'ok', ['readme' => $readme]);
    }
}
