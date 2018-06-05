<?php

namespace app\api\controller\v1;

use think\facade\Request;
use app\api\controller\v1\BasicController;
use app\api\service\Index as IndexService;
use app\api\service\Advertisement as AdvertisementService;
use app\api\service\AppLink as AppLinkService;

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
        $advertisementService = new AdvertisementService();
        $advertisementList = $advertisementService->getAdvertisementList();

        $appLinkService = new AppLinkService();
        $appLinkList = $appLinkService->getAppLinkList(2);

        return result(200, 'ok', [
            'faker_list' => $fakerList,
            'index_info' => $indexInfo,
            'advertisement_list' => $advertisementList,
            'app_link_list' => $appLinkList,
        ]);
    }

    /**
     * 排行榜
     * @return json
     */
    public function top()
    {
        $indexService = new IndexService();
        $honorList = $indexService->getHonorList();
        $willList = $indexService->getWillList();

        return result(200, 'ok', [
            'honor_list' => $honorList,
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
