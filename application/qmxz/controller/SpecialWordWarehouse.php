<?php

namespace app\qmxz\controller;

use app\qmxz\model\SpecialWordWarehouse as SpecialWordWarehouseModel;
use app\qmxz\model\SpecialWarehouse as SpecialWarehouseModel;
use app\qmxz\validate\SpecialWordWarehouse as SpecialWordWarehouseValidate;
use controller\BasicAdmin;

//整点场题库控制器类
class SpecialWordWarehouse extends BasicAdmin
{

    //字段验证
    protected function checkData($data)
    {
        $validate = new SpecialWordWarehouseValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title = '整点场题库';

        list($get, $db) = [$this->request->get(), new SpecialWordWarehouseModel()];

        $db = $db->search($get);

        $this->special_warehouse_list();
        return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['options']     = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
            $model               = new SpecialWordWarehouseModel();
            $data['create_time'] = time();

            if ($this->checkData($data) === true && $model->save($data) != false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_warehouse_list();
        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo        = SpecialWordWarehouseModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
            $post_data['options'] = json_encode($post_data['options'], JSON_UNESCAPED_UNICODE);
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_warehouse_list();
        return $this->fetch('edit', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = SpecialWordWarehouseModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    protected function special_warehouse_list()
    {
        $data = SpecialWarehouseModel::column('title', 'id');

        $this->assign('special_warehouse_list', $data);
    }
}
