<?php

namespace api_data_service\dcqw_xyx;

use think\facade\Cache;
use think\facade\Config;
use model\User as UserModel;
use api_data_service\Config as ConfigService;
use model\UserRecord as UserRecordModel;
use model\UserLevel as UserLevelModel;

class Index
{
    /**
     * 获取首页信息
     * @param $userId
     * @return array
     */
    public function getIndexInfo($userId, $version = '')
    {
        $user = UserModel::get($userId);
        $who = "有人";
        if ($user->nickname != '') {
            $who = $user->nickname;
        }

        $configService = new ConfigService();
        $config_data = $configService->getAll();

        $openOtherApp = $this->getConfigValue($config_data, 'open_other_app');
        $openShareUser = $this->getConfigValue($config_data, 'open_share_user');
        $shareToUserSuccessText =  $openShareUser ? $this->getConfigValue($config_data, 'share_to_user_success_text_when_open_share_user') : $this->getConfigValue($config_data, 'share_to_user_success_text_when_close_share_user');
        $shareToUserLimitText = $openShareUser ? $this->getConfigValue($config_data, 'share_to_user_Limit_text_when_open_share_user') : $this->getConfigValue($config_data, 'share_to_user_Limit_text_when_close_share_user');

        return [
            'complain_txt' => $this->getConfigValue($config_data,'complain_txt'),  //投诉内容
            'readme' => $this->getConfigValue($config_data,'readme'), //规则
            'index_share_title' => sprintf($this->getConfigValue($config_data,'index_share_title'), $who), //分享的标题
            'index_share_img' => $this->getConfigValue($config_data,'index_share_img'),  //分享的图片
            'index_other_appid' => $openOtherApp ? $this->getConfigValue($config_data,'index_other_appid') : '', //跳转的appid
            'index_other_path' => $openOtherApp ? $this->getConfigValue($config_data,'index_other_path') : '', //跳转的路径
            'guangdiantong' => $this->getConfigValue($config_data,'guangdiantong'), //广点通
            'hezi_appid' => $this->getConfigValue($config_data,'hezi_appid'), //盒子的appid
            'hezi_path' => $this->getConfigValue($config_data,'hezi_path'), //盒子的路径
            'index_middle_img_txt' => $this->getConfigValue($config_data,'index_middle_img_txt'),
            'chance_num' => $user->userRecord->chance_num,
        ];
    }

    private function  getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key]: '';
    }

    public function getSuccessList()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = config('success_key');
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $userRecordModel = new UserRecordModel();
            $list = $userRecordModel->getSuccessList();
            $expire = ConfigService::get('wealth_refresh_time') * 60;
            Cache::set($cacheKey, $list, $expire);

            return $list;
        }

    }

    public function getWillList()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = config('will_key');
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

}