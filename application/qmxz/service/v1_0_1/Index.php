<?php

namespace app\qmxz\service\v1_0_1;

use think\Db;
use app\qmxz\model\User as UserModel;
use app\qmxz\model\UserRecord as UserRecordModel;
use app\qmxz\service\Config as ConfigService;

/**
 * 首页服务类
 */
class Index
{
	protected $configData;
	/*
    public function __construct($configData)
    {
        $this->configData = $configData;
    }

	public function index($data)
	{

		echo "<pre>"; print_r($this->configData);exit();
	}
	*/
	public function hot_goods(){
		//筛选最火热的四件兑换商品的SQL: 
		//SELECT good_id,count(*) as nums FROM `dbqmxz`.`t_exchange_log` GROUP BY good_id ORDER BY nums desc LIMIT 4
		$hot_exchange = Db::name('exchange_log')->field('good_id,count(*) as nums')->group('good_id')->order('nums desc')->limit(4)->select();
		$hot_good_ids = '';
		foreach ($hot_exchange as $k => $v) {
			$hot_good_ids .= $v['good_id'] . ',';
		}
		$hot_good_ids = substr($hot_good_ids, 0, -1);
		$hot_goods = Db::name('goods')->field('id,title, img, stock, price')->whereIn('id',$hot_good_ids)->select();
		return $hot_goods;
	}
}