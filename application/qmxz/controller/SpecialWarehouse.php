<?php

namespace app\qmxz\controller;

use app\qmxz\model\SpecialWarehouse as SpecialWarehouseModel;
use app\qmxz\model\SpecialPrize as SpecialPrizeModel;
use controller\BasicAdmin;
use think\Db;

//整点场库控制器类
class SpecialWarehouse extends BasicAdmin
{
    public function index()
    {
        $this->title = '整点场库';

        list($get, $db) = [$this->request->get(), new SpecialWarehouseModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);

        foreach ($result['list'] as $key => $value) {
            $prize_info                         = Db::name('special_prize')->find($value['prize_id']);
            $result['list'][$key]['prize_name'] = $prize_info['name'];
            $result['list'][$key]['prize_img']  = $prize_info['img'];
            $result['list'][$key]['banners']    = json_decode($value['banners']);
        }
        $this->assign('title',$this->title);
        return $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['banners']      = json_encode($data['banners'], JSON_UNESCAPED_UNICODE);
            $model                = new SpecialWarehouseModel();
            $data['create_time']  = time();

            if ($model->save($data) != false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->prize_list();
        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo        = SpecialWarehouseModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
            $post_data['banners']      = json_encode($post_data['banners'], JSON_UNESCAPED_UNICODE);

            if ($vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->prize_list();
        return $this->fetch('edit', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = SpecialWarehouseModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    protected function prize_list()
    {
        $data = SpecialPrizeModel::where('status', 1)->column('name', 'id');
        $this->assign('prize_list', $data);
    }
}
