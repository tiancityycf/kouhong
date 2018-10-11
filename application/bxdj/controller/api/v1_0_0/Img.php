<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use service\WangSuService;
use think\Db;

class Img extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'img';

    public function index()
    {
    	$this->title = '图片管理';

       	list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['title'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        if (isset($get['status']) && $get['status'] !== '') {
            $db->where('status', '=', $get['status']);
        }

       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@img/index', $result);
    }

    public function add()
    {
    	return $this->_form($this->table, 'admin@img/form');
    }

    public function edit()
    {
        return $this->_form($this->table, 'admin@img/form');
    }

    public function del()
    {
    	$data = $this->request->post();
    	if($data){
    		if(Db::name($this->table)->where('id', $data['id'])->delete()){
    			$this->success("删除成功！", '');
    		}
    	}

    	$this->error("删除失败，请稍候再试！");
    }


    public function upload()
    {
    	$file = request()->file('file');
		$info = $file->validate(['ext'=>'jpg,png,gif']);
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