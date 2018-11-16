<?php

namespace app\khj\controller\api\v1_0_1;

use app\khj\service\v1_0_1\Index as IndexService;
use controller\BasicController;
use think\Db;
use think\facade\Request;

/**
 * 首页接口控制器类
 */
class Index extends BasicController
{

    /**
     * 用户中心接口
     * @return boolean
     */
    public function center()
    {
        echo 1;die;
        //echo "<pre>"; print_r($this->configData);exit();
        require_params('user_id');
        $user_id             = Request::param('user_id');
        $result['user_info'] = Db::name('user_record')->field('avatar,nickname,gold,money')->where('user_id', $user_id)->find();
        $rules               = $this->configData['rules'];

        foreach ($rules as $key => $value) {
            list($result['rules'][$key]['title'], $contents) = explode('|', $value);
            if (strpos($contents, '&')) {
                $result['rules'][$key]['content'] = explode('&', $contents);
            } else {

                $result['rules'][$key]['content'][] = $contents;
            };
        }

        $common_problems = $this->configData['common_problems'];

        foreach ($common_problems as $k2 => $v2) {
            list($result['common_problems'][$k2]['title'], $problems) = explode('|', $v2);
            if (strpos($problems, '&')) {
                $result['common_problems'][$k2]['content'] = explode('&', $problems);
                $result['common_problems'][$k2]['id']      = $k2 + 1;
            } else {

                $result['common_problems'][$k2]['content'][] = $problems;
                $result['common_problems'][$k2]['id']        = $k2 + 1;
            };
        }

        $result['common_problems_banner'] = $this->configData['common_promble_banner'];

        return result(200, 'ok', $result);
    }

    /**
     * 玩家金币排行接口
     * @return boolean
     */
    public function rank()
    {
        echo 1;die;
        $config_data = $this->configData;
        //默认用户名
        $system_default_nickname = $config_data['system_default_nickname'];
        //默认头像
        $system_default_avatar = $config_data['system_default_avatar'];
        $rank_nums             = $config_data['rank_nums'] ? $config_data['rank_nums'] : 5;
        $rankers               = Db::name('user_record')->field('avatar,nickname,gold')->order('gold desc')->limit($rank_nums)->select();
        if (!empty($rankers)) {
            foreach ($rankers as $key => $value) {
                if ($value['avatar'] == '') {
                    $rankers[$key]['avatar'] = $system_default_avatar;
                }
                if ($value['nickname'] == '') {
                    $rankers[$key]['nickname'] = $system_default_nickname;
                }
            }
        }
        return result(200, 'ok', $rankers);
    }

}
