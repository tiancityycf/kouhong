<?php
namespace app\khj2\controller;

use app\khj2\model\DailyTask as DailyTaskModel;
use app\khj2\model\RechargeAmount as RechargeAmountModel;
use app\khj2\validate\DailyTask as DailyTaskValidate;
use controller\BasicAdmin;

class DailyTask extends BasicAdmin
{

    //字段验证
    protected function checkData($data)
    {
        $validate = new DailyTaskValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title    = '每日任务';
        list($get, $db) = [$this->request->get(), new DailyTaskModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);

        $this->recharge_list();
        $this->times_list();
        $this->action_list();

        return $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $model = new DailyTaskModel();
            if ($this->checkData($data) === true && $model->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        $this->recharge_list();
        $this->times_list();
        $this->action_list();

        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = DailyTaskModel::get($get_data['id']);

        $post_data = $this->request->post();
        if ($post_data) {
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        $this->recharge_list();
        $this->times_list();
        $this->action_list();

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
            $model         = DailyTaskModel::get($data['id']);
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
            $model         = DailyTaskModel::get($data['id']);
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
            if (DailyTaskModel::where('id', $data['id'])->delete()) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    private function recharge_list()
    {
        $data = RechargeAmountModel::column('title', 'id');
        $this->assign('recharge_list', $data);
    }

    private function times_list()
    {
        $times_list = [];
        for ($i = 0; $i <= 10; $i++) {
            if ($i == 0) {
                $times_list[$i] = '不限次数';
            } else {
                $times_list[$i] = $i . '次';
            }
        }
        $this->assign('times_list', $times_list);
    }

    private function action_list()
    {
        $action_list = [
            'share'    => '分享',
            'recharge' => '充值',
        ];
        $this->assign('action_list', $action_list);
    }
}
