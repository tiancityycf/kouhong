<?php
namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use service\WangSuService;
use think\Db;


class Upload extends BasicAdmin
{
	public function index()
	{
		return $this->fetch();
	}

	public function upload()
	{
		$file = request()->file('file');
		$info = $file->validate(['ext'=>'xlsx']);
		if($info){
			$file_info = $file->getInfo();

			$obj = new WangSuService();

			$res = $obj->upload($file_info);

			return $res;

		} else  {
			return json_encode(['code' => 0, 'msg' => '上传文件格式错误']);
		}
	}
}