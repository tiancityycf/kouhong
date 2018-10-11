<?php

namespace api_data_service\v1_0_9_1;

use think\facade\Cache;
use think\facade\Config;
use model\User as UserModel;
use api_data_service\Config as ConfigService;
use model\UserRecord as UserRecordModel;

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
    public function getIndexInfo($userId)
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
            'index_other_appid' => $openOtherApp ? $this->getConfigValue($config_data,'index_other_appid') : '',
            'index_other_path' => $openOtherApp ? $this->getConfigValue($config_data,'index_other_path') : '',
            'index_middle_img_txt' => $this->getConfigValue($config_data,'index_middle_img_txt'),
            'index_middle_img_txt_hei' => $this->getConfigValue($config_data,'index_middle_img_txt_hei'),
            'index_share_title' => sprintf($this->getConfigValue($config_data,'index_share_title'), $who),
            'index_share_img' => $this->getConfigValue($config_data,'index_share_img'),
            'user_index_share_title' => sprintf($this->getConfigValue($config_data,'user_index_share_title'), $who),
            'user_index_share_img' => $this->getConfigValue($config_data,'user_index_share_img'),
            'after_challenge_share_title' => sprintf($this->getConfigValue($config_data,'after_challenge_share_title'), $who),
            'after_challenge_share_img' => $this->getConfigValue($config_data,'after_challenge_share_img'),
            'share_to_group_success_text' => $this->getConfigValue($config_data,'share_to_group_success_text'),
            'share_to_group_repeat_text' => $this->getConfigValue($config_data,'share_to_group_repeat_text'),
            'share_to_group_limit_text' => $this->getConfigValue($config_data,'share_to_group_limit_text'),
            'share_to_user_success_text' => $shareToUserSuccessText,
            'share_to_user_limit_text' => $shareToUserLimitText,
            'open_challenge_unlimit' => $this->getConfigValue($config_data,'open_challenge_unlimit'),
            'game_failed_text_again' => $this->getConfigValue($config_data,'game_failed_text_again'),
            'game_failed_text_friends' => $this->getConfigValue($config_data,'game_failed_text_friends'),
            'index_share_text' => $this->getConfigValue($config_data,'index_share_text'),
            'index_share_qun_text' => $this->getConfigValue($config_data,'index_share_qun_text'),
            'withdraw_rule' => $this->getConfigValue($config_data,'withdraw_rule'),
            'tiaozhuankongzhi' => $this->getConfigValue($config_data,'tiaozhuankongzhi'),
            'tianxian_appid' => $this->getConfigValue($config_data,'tianxian_appid'),
            'tixian_app_path' => $this->getConfigValue($config_data,'tixian_app_path'),
            'tixian_anniu' => $this->getConfigValue($config_data,'tixian_anniu'),
            'chance_num' => $user->userRecord->chance_num,
            'success_num' => $user->userRecord->success_num,
            'gold' => $user->userRecord->gold,
            'pifu' => $user->userRecord->pifu_id ? $user->userRecord->pifu->img : '',
            'lianxi_jixu_anniu' => $this->getConfigValue($config_data,'lianxi_jixu_anniu'),
            'lianxi_huode_anniu' => $this->getConfigValue($config_data,'lianxi_huode_anniu'),
            'hongbaoruchangquan' => $this->getConfigValue($config_data,'hongbaoruchangquan'),
            'hongbao_yaoqing' => $this->getConfigValue($config_data,'hongbao_yaoqing'),
            'qulianxichang' => $this->getConfigValue($config_data,'qulianxichang'),
            'lianxi_success_txt' => $this->getConfigValue($config_data,'lianxi_success_txt'),
            'lianxi_top_title' => $this->getConfigValue($config_data,'lianxi_top_title'),
            'success_three_withdraw' => $this->getConfigValue($config_data,'success_three_withdraw'),
            'input_on_off' => $this->getConfigValue($config_data,'input_on_off'),
            'allow_success_num' => $this->getConfigValue($config_data,'allow_success_num'),
            'jinru_lianxichang_tishi' => $this->getConfigValue($config_data,'jinru_lianxichang_tishi'),
            'first_withdraw_success_num' => $this->getConfigValue($config_data,'first_withdraw_success_num'),
            'fuzhi_danhao' => $this->getConfigValue($config_data,'fuzhi_danhao'),
            'youxihezi_anniu_txt' => $this->getConfigValue($config_data,'youxihezi_anniu_txt'),
            'guangdiantong' => $this->getConfigValue($config_data,'guangdiantong'),
            'hezi_appid' => $this->getConfigValue($config_data,'hezi_appid'),
            'hezi_path' => $this->getConfigValue($config_data,'hezi_path'),
            'tili_time_jiange' => $this->getConfigValue($config_data, 'tili_time_jiange'),
            'tili_limit' => $this->getConfigValue($config_data, 'tili_limit'),
            'chaihongbaoanniu' => $this->getConfigValue($config_data,'chaihongbaoanniu'),
            'hongbaofenxiangchongfu' => $this->getConfigValue($config_data,'hongbaofenxiangchongfu'),
            'hongbaofenxiang_limit' => $this->getConfigValue($config_data,'hongbaofenxiang_limit'),
        ];
    }

    private function  getConfigValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key]: '';
    }

    public function getYourList($user_id)
    {
        $model = UserRecordModel::where('user_id', $user_id)->find();

        $data = [];
        if ($model) {
            $data['user_id'] = $model->user_id;
            $data['avatar'] = $model->avatar;
            $data['nickname'] = $model->nickname;
            $data['success_num'] = $model->success_num;
        } else {
            $data['user_id'] = '';
            $data['avatar'] = '';
            $data['nickname'] = '';
            $data['success_num'] = '';
        }

        return $data;      
    }

    public function getCount($user_id, $wealthList, $willList)
    {
        $data = [];

        if ($wealthList) {
            $data['wealth_count'] = '10+';
            foreach ($wealthList as $k => $v) {
                $list_user_id = isset($v['user_id']) ? $v['user_id'] : 0;
                if ($user_id == $list_user_id) {
                    $data['wealth_count'] = $k + 1;
                    break;
                }
            }
        } else {
            $data['wealth_count'] = '';
        }

        if ($willList) {
            $data['will_count'] = '10+';
            foreach ($willList as $key => $value) {
                $list_user_id = isset($value['user_id']) ? $value['user_id'] : 0;
                if ($user_id == $list_user_id) {
                    $data['will_count'] = $key + 1;
                    break;
                }
            }
        } else {
            $data['will_count'] = '';
        }

        return $data;
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
            $list = $userRecordModel->getSuccessList();
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