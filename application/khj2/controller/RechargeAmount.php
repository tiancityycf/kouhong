<?php
namespace app\khj2\controller;

use controller\BasicAdmin;
use app\khj2\model\RechargeAmount as RechargeAmountModel;
use app\khj2\validate\RechargeAmount as RechargeAmountValidate;

class RechargeAmount extends BasicAdmin
{
    //字段验证
    protected function checkData($data)
    {
        $validate = new RechargeAmountValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title = '金额配置';
        list($get, $db) = [$this->request->get(), new RechargeAmountModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $model = new RechargeAmountModel();
            if ($this->checkData($data) === true && $model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = RechargeAmountModel::get($get_data['id']);

        $post_data = $this->request->post();
        if ($post_data) {
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return $this->fetch('form', ['vo' => $vo->toArray()]);
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
            $model         = RechargeAmountModel::get($data['id']);
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
            $model         = RechargeAmountModel::get($data['id']);
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
        if ($data) {
            if (RechargeAmountModel::where('id', $data['id'])->delete()) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }
}
