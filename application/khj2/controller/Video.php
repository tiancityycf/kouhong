<?php
namespace app\khj2\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\khj2\model\Video as VideoModel;


class Video extends BasicAdmin
{
	/**
	 * 指定当前数据表
	 * @var string
	 */

	public function index()
	{
		$this->title = '管理';

		list($get, $db) = [$this->request->get(), new VideoModel()];

		$db = $db->search($get);

		$result = parent::_list($db, true, false, false);
		$this->assign('title', $this->title);

		return  $this->fetch('index', $result);
	}

}
