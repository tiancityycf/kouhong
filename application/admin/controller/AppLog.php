<?php
namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\admin\model\Advertisement as AdvertisementModel;
use app\admin\model\AdvertisementLog as AdvertisementLogModel;
use app\admin\model\AppLink as AppLinkModel;
use app\admin\model\LinkLog as LinkLogModel;

class AppLog extends BasicAdmin
{
	public $ad_log_table = 'advertisement_log';
	public $ad_table = 'advertisement';
	public $link_log_table = 'link_log';
	public $link_table = 'app_link';


	public function index()
	{
		$this->title = '点击统计';

		$get = $this->request->get();

		$ad_lg_list = Db::name('advertisement')->alias('ad')
			->leftJoin('t_advertisement_log adl','adl.advertisement_id = ad.id')
			->group('ad.appid');
			

		$al_lg_list = Db::name('app_link')->alias('al')
			->leftJoin('t_link_log ll','ll.app_id = al.id')
			->group('al.appid');
			

		if (isset($get['appid']) && $get['appid'] !== '') {
        	$ad_lg_list->whereLike('ad.appid', "%{$get['appid']}%");
        	$al_lg_list->whereLike('al.appid', "%{$get['appid']}%");
        }

        if (isset($get['start_time']) && $get['start_time'] !== '') {
            $ad_lg_list->whereTime('create_time', '>=', strtotime($get['start_time']));
            $al_lg_list->whereTime('create_time', '>=', strtotime($get['start_time']));
        }

        if (isset($get['end_time']) && $get['end_time'] !== '') {
            $ad_lg_list->whereTime('create_time', '<=', strtotime($get['end_time']));
            $al_lg_list->whereTime('create_time', '<=', strtotime($get['end_time']));
        }

        $ad_lg_list = $ad_lg_list->column('ad.xcx_img as img, ad.appid, count(distinct(adl.user_id)) as total', 'ad.appid');
        $al_lg_list = $al_lg_list->column('al.app_icon as img, al.appid, count(distinct(ll.user_id)) as total', 'al.appid');

		$ad_lg_today_list = Db::name('advertisement')->alias('ad')
			->leftJoin('t_advertisement_log adl','adl.advertisement_id = ad.id')
			->whereTime('create_time', '>=', strtotime(date("Y-m-d"),time()))
			->group('ad.appid')
			->column('count(distinct(adl.user_id)) as total', 'ad.appid');

		$al_lg_today_list = Db::name('app_link')->alias('al')
			->leftJoin('t_link_log ll','ll.app_id = al.id')
			->whereTime('create_time', '>=', strtotime(date("Y-m-d"),time()))
			->group('al.appid')
			->column('count(distinct(ll.user_id)) as total', 'al.appid');

		

		foreach ($ad_lg_list as $key => $value) {
			if (array_key_exists($key, $al_lg_list)) {
				$al_lg_list[$key]['total'] += $value['total'];
			} else {
				$al_lg_list[$key]['img'] = $value['img'];
				$al_lg_list[$key]['appid'] = $value['appid'];
				$al_lg_list[$key]['total'] = $value['total'];
			}
		}

		foreach ($ad_lg_today_list as $k => $v) {
			if (array_key_exists($k, $al_lg_today_list)) {
				$al_lg_today_list[$k] += $v;
			} else {
				$al_lg_today_list[$k] = $v;
			}
		}

		$this->assign('list_taday', $al_lg_today_list);
		return  $this->fetch('index', ['list' => $al_lg_list]);

	}

}