<?php

namespace app\khj2\service\v1_0_1;

use app\khj2\model\SpecialPrize as SpecialPrizeModel;
use app\khj2\model\User as UserModel;
use app\khj2\model\UserSpecialPrize as UserSpecialPrizeModel;
use app\khj2\model\ExchangeLog as ExchangeLogModel;
use app\khj2\model\Goods as GoodsModel;
use think\Db;

/**
 * 首页服务类
 */
class Index
{
    protected $configData;

    public function __construct($configData = [])
    {
        $this->configData = $configData;
    }

    public function hot_goods()
    {
        //筛选最火热的四件兑换商品的SQL:
        //SELECT good_id,count(*) as nums FROM `dbkhj2`.`t_exchange_log` GROUP BY good_id ORDER BY nums desc LIMIT 4
        // $hot_exchange = Db::name('exchange_log')->field('good_id,count(*) as nums')->group('good_id')->order('nums desc')->limit(4)->select();
        $hot_exchange = Db::name('exchange_log')->field('good_id,count(*) as nums')->group('good_id')->order('nums desc')->select();
        $hot_good_ids = '';
        foreach ($hot_exchange as $k => $v) {
            $hot_good_ids .= $v['good_id'] . ',';
        }
        $hot_good_ids = substr($hot_good_ids, 0, -1);
        // $hot_goods = Db::name('goods')->field('id,title, img, stock, price')->where('is_index', 1)->whereIn('id',$hot_good_ids)->select();
        $hot_goods = Db::name('goods')->field('id,title, img, is_partner, partner_appid, jump_route, stock, price')->where('is_index', 1)->order('order desc')->select();

        foreach ($hot_goods as $k2 => $v2) {
            $hot_goods[$k2]['imgs']         = Db::name('good_imgs')->where('product_id', $v2['id'])->select();
            $hot_goods[$k2]['good_details'] = Db::name('good_details')->where('product_id', $v2['id'])->select();
        }

        return $hot_goods;
    }

    /**
     * 获取分享配置信息
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getShareInfo($data)
    {
        //分享文案
        $config_data    = $this->configData;
        $share_text_arr = $config_data['share_text_arr'];
        if (!empty($share_text_arr)) {
            $user_info = UserModel::where('openid', $data['openid'])->find();
            $now_time  = date('Y-m-d H:i:s');
            $rep       = ['{name}', '{time}'];
            $rep_arr   = [$user_info['nickname'], $now_time];
            foreach ($share_text_arr as $key => $value) {
                $share_text_arr[$key] = str_replace($rep, $rep_arr, $value);
            }
        }
        //分享图片
        $share_img_arr = $config_data['share_img_arr'];
        return [
            'share_text_arr' => $share_text_arr,
            'share_img_arr'  => $share_img_arr,
        ];
    }

    /**
     * 获取获奖信息
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function getPrizeList($data)
    {
        //获奖信息限制数量
        $config_data     = $this->configData;
        $prize_limit_num = $config_data['prize_limit_num'];
        $prize_list      = UserSpecialPrizeModel::limit($prize_limit_num)->order('addtime desc')->select();
        foreach ($prize_list as $key => $value) {
            //奖品信息
            $prize_info                     = SpecialPrizeModel::get($value['prize_id']);
            $prize_list[$key]['prize_name'] = $prize_info['name'];
            $prize_list[$key]['prize_img']  = $prize_info['img'];
            //用户信息
            $user_info                    = UserModel::get($value['user_id']);
            $prize_list[$key]['nickname'] = $user_info['nickname'];
            $prize_list[$key]['avatar']   = $user_info['avatar'];
        }
        return $prize_list;
    }

    /**
     * 获取兑换信息
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function getExchangeList($data)
    {
        //获奖信息限制数量
        $config_data     = $this->configData;
        $prize_limit_num = $config_data['prize_limit_num'];
        $exchange_list      = ExchangeLogModel::where('status', 1)->limit($prize_limit_num)->order('create_time desc')->select();
        foreach ($exchange_list as $key => $value) {
            $exchange_list[$key]['addtime']  = $value['create_time'];
            //奖品信息
            $good_info                     = GoodsModel::get($value['good_id']);
            $exchange_list[$key]['prize_name'] = $good_info['title'];
            $exchange_list[$key]['prize_img']  = $good_info['img'];
            //用户信息
            $user_info                    = UserModel::get($value['user_id']);
            $exchange_list[$key]['nickname'] = $user_info['nickname'];
            $exchange_list[$key]['avatar']   = $user_info['avatar'];
        }
        return $exchange_list;
    }
}
