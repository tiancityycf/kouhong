<?php

namespace app\api\service;

use think\Db;
use think\facade\Cache;
use app\api\model\Advertisement as AdvertisementModel;
use app\api\service\Config as ConfigService;

class Advertisement
{
	/**
     * 获取广告位列表
     * @return array
     */
    public function getAdvertisementList()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':advertisementlist';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $advertisementModel = new AdvertisementModel();
            $advertisementList = $advertisementModel->field('id as advertisement_id, type, open_ad, appid, path, position, xcx_img, position_type')->select();
            $expire = ConfigService::get('advertisement_refresh_time');

            Cache::set($cacheKey, $advertisementList, $expire);
            return $advertisementList;
        }
    }
}