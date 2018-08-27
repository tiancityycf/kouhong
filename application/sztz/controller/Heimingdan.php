<?php
namespace app\sztz\controller;

use controller\BasicAdmin;
use service\DataService;
use model\Heimingdan as HeimingdanModel;
use think\Db;

class Heimingdan extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'heimingdan';

	public function index()
    {
    	$this->title = '小程序的黑名单';

       	list($get, $db) = [$this->request->get(), new HeimingdanModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
       	return  $this->fetch('admin@heimingdan/index', $result);
    }

    //添加
    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $model = new HeimingdanModel();
            if ($model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        
        return  $this->fetch('admin@heimingdan/form', ['vo' => $data]);
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $data = $this->request->post();
        if ($data) {
            $model = HeimingdanModel::get($data['id']);
            $model->status = 0;
            if ($model->save() !== false) {
                $this->success("禁用成功！", '');
            }
        }

        $this->error("禁用失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $data = $this->request->post();
        if ($data) {
            $model = HeimingdanModel::get($data['id']);
            $model->status = 1;
            if ($model->save() !== false) {
                $this->success("启用成功！", '');
            }
        }

        $this->error("启用失败，请稍候再试！");
    }

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = HeimingdanModel::get($data['id']);
            if($model->delete() !== false){
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    public function  load()
    {
        $filename = 'heimingdan.csv';
        header("Content-type: application/octet-stream");
        header('Content-Disposition:attachment;filename = '.$filename);

        $fp = fopen('php://output', 'w');

        fputcsv($fp, ['ID', '昵称']);

        $all_data = HeimingdanModel::where('status', 0)->select();

        foreach ($all_data as $data) {
            fputcsv($fp, [$data->id, $data->nickname]);
        }
    }
}