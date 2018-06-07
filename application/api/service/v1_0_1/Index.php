<?php

namespace app\api\service\v1_0_1;

use think\Db;
use think\facade\Cache;
use think\facade\Config;
use app\api\model\User as UserModel;
use app\api\service\Config as ConfigService;
use app\api\model\UserRecord as UserRecordModel;
use app\api\model\RedpacketLog as RedpacketLogModel;

class Index
{
    /**
     * 获取用户须知
     * @return array
     */
    public function getReadme()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':readme';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $list = ConfigService::get('readme');
            $expire = ConfigService::get('readme_refresh_time');
            Cache::set($cacheKey, $list, $expire);

            return $list;
        }
    }

    /**
     * 获取伪造的中奖列表
     * @return array
     */
    public function getFakerWinPrizeList()
    {
        // TODO 昵称库中随机获取
        $now = time();
        return [
            /*['depend', 1, $now],
            ['梦醒时疯。', 1, $now],
            ['小白兔奶糖', 1, $now],
            ['续宇峰', 1, $now],
            ['三个石头', 1, $now],
            ['放下自尊', 1, $now],*/
        ];
    }

    /**
     * 获取首页信息
     * @param $userId
     * @return array
     */
    public function getIndexInfo($userId)
    {
        $user = UserModel::get($userId);
        $who = "有人";
        if ($user->nickname != '') {
            $who = $user->nickname;
        }

        $openOtherApp = ConfigService::get('open_other_app');
        $openShareUser = ConfigService::get('open_share_user');
        $shareToUserSuccessText =  $openShareUser ? ConfigService::get('share_to_user_success_text_when_open_share_user') : ConfigService::get('share_to_user_success_text_when_close_share_user');
        $shareToUserLimitText = $openShareUser ? ConfigService::get('share_to_user_Limit_text_when_open_share_user') : ConfigService::get('share_to_user_Limit_text_when_close_share_user');

        return [
            'index_other_appid' => $openOtherApp ? ConfigService::get('index_other_appid') : '',
            'index_other_path' => $openOtherApp ? ConfigService::get('index_other_path') : '',
            'index_middle_img_txt' => ConfigService::get('index_middle_img_txt'),
            'index_share_title' => sprintf(ConfigService::get('index_share_title'), $who),
            'index_share_img' => ConfigService::get('index_share_img'),
            'user_index_share_title' => sprintf(ConfigService::get('user_index_share_title'), $who),
            'user_index_share_img' => ConfigService::get('user_index_share_img'),
            'after_challenge_share_title' => sprintf(ConfigService::get('after_challenge_share_title'), $who),
            'after_challenge_share_img' => ConfigService::get('after_challenge_share_img'),
            'share_to_group_success_text' => ConfigService::get('share_to_group_success_text'),
            'share_to_group_repeat_text' => ConfigService::get('share_to_group_repeat_text'),
            'share_to_group_limit_text' => ConfigService::get('share_to_group_limit_text'),
            'share_to_user_success_text' => $shareToUserSuccessText,
            'share_to_user_limit_text' => $shareToUserLimitText,
            'open_challenge_unlimit' => ConfigService::get('open_challenge_unlimit'),
        ];
    }

	/**
	 * 获取荣誉榜
	 * @return array
	 */
    public function getHonorList()
    {
    	// 如果缓存没有，则去数据库获取
    	$cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':honorlist';
    	if (Cache::has($cacheKey)) {
    		return Cache::get($cacheKey);
    	} else {
    		$userRecordModel = new UserRecordModel();
    		$list = $userRecordModel->getHonorList();
    		$expire = ConfigService::get('honor_refresh_time') * 60;
    		Cache::set($cacheKey, $list, $expire);

    		return $list;
    	}
    }

    /**
     * 获取毅力榜
     * @return array
     */
    public function getWillList()
    {
    	// 如果缓存没有，则去数据库获取
    	$cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':willlist';
    	if (Cache::has($cacheKey)) {
    		return Cache::get($cacheKey);
    	} else {
    		$userRecordModel = new UserRecordModel();
    		$list = $userRecordModel->getWillList();
    		$expire = ConfigService::get('will_refresh_time') * 60;
    		Cache::set($cacheKey, $list, $expire);

    		return $list;
    	}
    }

    public function getWealthList()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = CACHE_APP_NAME . ':' . CACHE_APP_UNIQ . ':wealthlist';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $userRecordModel = new UserRecordModel();
            $list = $userRecordModel->getWealthList();
            $expire = ConfigService::get('wealth_refresh_time') * 60;
            Cache::set($cacheKey, $list, $expire);

            return $list;
        }

    }
}