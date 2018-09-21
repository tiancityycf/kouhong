<?php

namespace api_data_service\v2_0_2;

use think\facade\Cache;
use think\facade\Config;
use model\User as UserModel;
use api_data_service\Config as ConfigService;
use model\UserRecord as UserRecordModel;
use model\UserLevel as UserLevelModel;

class Index
{
    /**
     * 获取用户须知
     * @return array
     */
    public function getReadme()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = config('readme_key');
        if (Cache::has($cacheKey)) {
            $list = Cache::get($cacheKey);
        } else {
            $list = ConfigService::get('readme');
            $expire = ConfigService::get('readme_refresh_time');
            Cache::set($cacheKey, $list, $expire);

            
        }

        return [
            'readme' => $list,
            'complain_txt' => ConfigService::get('complain_txt'),
        ];
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

        $jxdn_in_off = $this->getConfigValue($config_data,'in_off_version') == $version ? 1 : 0;

        return [
            'complain_txt' => $this->getConfigValue($config_data,'complain_txt'),  //投诉内容
            'readme' => $this->getConfigValue($config_data,'readme'), //规则
            'index_share_title' => sprintf($this->getConfigValue($config_data,'index_share_title'), $who), //分享的标题
            'index_share_img' => $this->getConfigValue($config_data,'index_share_img'),  //分享的图片
            'index_other_appid' => $openOtherApp ? $this->getConfigValue($config_data,'index_other_appid') : '', //跳转的appid
            'index_other_path' => $openOtherApp ? $this->getConfigValue($config_data,'index_other_path') : '', //跳转的路径
            'tiaozhuankongzhi' => $this->getConfigValue($config_data,'tiaozhuankongzhi'),  //跳转控制 0不跳转 1当天跳转一次 2永久跳转
            'success_num' => $user->userRecord->success_num,  //成功通关次数
            'allow_success_num' => $this->getConfigValue($config_data,'allow_success_num'), //允许挑战的成功次数
            'tianxian_appid' => $this->getConfigValue($config_data,'tianxian_appid'), //跳转提现的appid
            'tixian_app_path' => $this->getConfigValue($config_data,'tixian_app_path'), //跳转提现的路径
            'tixian_anniu' => $this->getConfigValue($config_data,'tixian_anniu'),  //提现按钮
            'withdraw_rule' => $this->getConfigValue($config_data,'withdraw_rule'), //提现规则
            'guangdiantong' => $this->getConfigValue($config_data,'guangdiantong'), //广点通
            'hezi_appid' => $this->getConfigValue($config_data,'hezi_appid'), //盒子的appid
            'hezi_path' => $this->getConfigValue($config_data,'hezi_path'), //盒子的路径
            'chaihongbaoanniu' => $this->getConfigValue($config_data,'chaihongbaoanniu'), //拆红包按钮
            'hongbaofenxiangchongfu' => $this->getConfigValue($config_data,'hongbaofenxiangchongfu'), //红包分享到重复群
            'hongbaofenxiang_limit' => $this->getConfigValue($config_data,'hongbaofenxiang_limit'), //红包分享达上限
            'level_config' => $this->getLevel($userId),  //当前等级的局数配置
            'share_huanpai_success' => $this->getConfigValue($config_data,'share_huanpai_success'), //分享换牌成功的文字
            'share_huanpai_chongfu' => $this->getConfigValue($config_data,'share_huanpai_chongfu'), //分享换牌到重复群
            'share_huanp_limit' => $this->getConfigValue($config_data,'share_huanp_limit'), // 分享换牌达上限
            'first_withdraw_success_num' => $this->getConfigValue($config_data,'first_withdraw_success_num'),
            'success_three_withdraw' => $this->getConfigValue($config_data,'success_three_withdraw'),
            'wen_xin_ti_shi' => $this->getConfigValue($config_data,'wen_xin_ti_shi'),
            'jxdn_in_off' => $jxdn_in_off,
            'fuzhi_danhao' => $this->getConfigValue($config_data,'fuzhi_danhao'),
        ];
    }

    private function  getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key]: '';
    }

    private function getLevel($user_id)
    {
        $userRecord = UserRecordModel::where('user_id', $user_id)->find();
        $info = UserLevelModel::where('id', $userRecord->user_level)->find();

        //return ['total_num' => $info->total_num, 'tongguan_num' => $info->tongguan_num];
        return $info->total_num."局".$info->tongguan_num."胜";
    }

	/**
	 * 获取荣誉榜
	 * @return array
	 */
    public function getHonorList()
    {
    	// 如果缓存没有，则去数据库获取
    	$cacheKey = config('honor_key');
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


    public function getWealthList()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = config('wealth_key');
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

    public function getSuccessList()
    {
        // 如果缓存没有，则去数据库获取
        $cacheKey = config('success_key');
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $userRecordModel = new UserRecordModel();
            $list = $userRecordModel->getRedpacketList();
            $expire = ConfigService::get('wealth_refresh_time') * 60;
            Cache::set($cacheKey, $list, $expire);

            return $list;
        }

    }

    public function check($data)
    {
        $model = UserRecordModel::where('user_id', $data['user_id'])->find();

        if ($model) {
            $model->tiaozhuan_num += 1;
            $model->save();

            $status = 1;
        } else {
            $status = 0;
        }

        return ['status' => $status];
    }


    public function tixian_info()
    {
        $configService = new ConfigService();
        $config_data = $configService->getAll();
        
        return [
            'tixian_chenggong' => $this->getConfigValue($config_data, 'tixian_chenggong'),
            'tixian_shibai' => $this->getConfigValue($config_data, 'tixian_shibai'),
            'tixian_dashangxian' => $this->getConfigValue($config_data, 'tixian_dashangxian'),
            'tixian_danhao_yishiyong' => $this->getConfigValue($config_data, 'tixian_danhao_yishiyong'),
            'zhaobudao_danhao' => $this->getConfigValue($config_data, 'zhaobudao_danhao'),
            'tixian_moren_wenzi' => $this->getConfigValue($config_data, 'tixian_moren_wenzi'),
        ];
    }
}