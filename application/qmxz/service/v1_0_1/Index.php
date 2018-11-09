<?php

namespace app\qmxz\service\v1_0_1;

use think\Db;

/**
 * 首页服务类
 */
class Index
{
    protected $configData;

    public function __construct($configData=[])
    {
        $this->configData = $configData;
    }

    public function hot_goods()
    {
        //筛选最火热的四件兑换商品的SQL:
        //SELECT good_id,count(*) as nums FROM `dbqmxz`.`t_exchange_log` GROUP BY good_id ORDER BY nums desc LIMIT 4
        // $hot_exchange = Db::name('exchange_log')->field('good_id,count(*) as nums')->group('good_id')->order('nums desc')->limit(4)->select();
        $hot_exchange = Db::name('exchange_log')->field('good_id,count(*) as nums')->group('good_id')->order('nums desc')->select();
        $hot_good_ids = '';
        foreach ($hot_exchange as $k => $v) {
            $hot_good_ids .= $v['good_id'] . ',';
        }
        $hot_good_ids = substr($hot_good_ids, 0, -1);
        // $hot_goods = Db::name('goods')->field('id,title, img, stock, price')->where('is_index', 1)->whereIn('id',$hot_good_ids)->select();
        $hot_goods = Db::name('goods')->field('id,title, img, is_partner, partner_appid, stock, price')->where('is_index', 1)->order('order desc')->select();

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
    public function getShareInfo($data){
        //分享文案
        $config_data = $this->configData;
        $share_text_arr = $config_data['share_text_arr'];
        dump($share_text_arr);exit;
    }
}
