<?php

namespace app\api\service;

use think\facade\Cache;
use app\api\model\AppLink as AppLinkModel;
use app\api\service\Config as ConfigService;

/**
 * 推广链接服务类
 */
class AppLink
{
	/**
	 * 获取推广链接
	 * @return array
     * @param position 1 => '更多好玩', 2 => '首页推广'
	 */
	public function getAppLinkList($position = 1)
	{
		// 如果缓存没有，则去数据库获取
        $cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':applinklist';
        if (Cache::has($cacheKey)) {
           $appLinkList = Cache::get($cacheKey);
        } else {
            $appLinkList = AppLinkModel::where('status', 1)->order('sort_order', 'desc')->select();
            $expire = ConfigService::get('applink_refresh_time');
            Cache::set($cacheKey, $appLinkList, $expire);
        }

        $linkList = [];
        foreach ($appLinkList as $key => $appLink) {
            if ($appLink['position'] == $position) {
                $linkList[$key] = [
                    'app_id' => $appLink['id'],
                    'wx_appid' => $appLink['appid'],
                    'app_title' => $appLink['app_title'],
                    'app_desc' => $appLink['app_desc'],
                    'app_icon' => $appLink['app_icon'],
                    'link_text' => $appLink['link_text'],
                    'link_path' => $appLink['link_path'],
                ];
            }
        }

        return $linkList;
	}
}