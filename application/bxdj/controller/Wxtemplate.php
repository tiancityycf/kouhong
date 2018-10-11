<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;

use app\bxdj\model\Wxtemplate as WxtemplateModel;

//use model\Cate as CateModel;
//use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use api_data_service\Config as ConfigService;
use service\WangSuService;
use think\facade\Cache;

class Wxtemplate extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'template';

	public function index()
    {
    	$this->title = '微信模板';

       	list($get, $db) = [$this->request->get(), new WxtemplateModel()];

        //$db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
       	return  $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();

        if ($data) {
            $model = new WxtemplateModel();
            if ($model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $model = new WxtemplateModel();

        $vo = $model->where('id',$get_data['id'])->find();

        $post_data = $this->request->post();

        if ($post_data) {
            if ($vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('form', ['vo' => $vo->toArray()]);

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
            $model = WxtemplateModel::get($data['id']);
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
            $model = WxtemplateModel::get($data['id']);
            $model->status = 1;
            if ($model->save() !== false) {
                $this->success("启用成功！", '');
            }
        }

        $this->error("启用失败，请稍候再试！");
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

   
}