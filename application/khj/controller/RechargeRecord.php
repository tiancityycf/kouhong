<?php
namespace app\khj\controller;

use controller\BasicAdmin;
use app\khj\model\Order as OrderModel;

class RechargeRecord extends BasicAdmin
{

    public function index()
    {
        $this->title = '充值记录';
        list($get, $db) = [$this->request->get(), new OrderModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('index', $result);
    }

    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            if (OrderModel::where('id', $data['id'])->delete()) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }
}
