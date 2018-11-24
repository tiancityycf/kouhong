<?php
namespace app\khj2\controller;

use app\khj2\model\Goods as GoodsModel;
use app\khj2\model\Order as OrderModel;
use app\khj2\model\User as UserModel;
use controller\BasicAdmin;

class RechargeRecord extends BasicAdmin
{

    public function index()
    {
        $this->title    = '充值记录';
        list($get, $db) = [$this->request->get(), new OrderModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        foreach ($result['list'] as $key => $value) {
            //用户信息
            $user_info                        = UserModel::get($value['user_id']);
            $result['list'][$key]['nickname'] = $user_info['nickname'];
            $result['list'][$key]['avatar']   = $user_info['avatar'];
            //商品信息
            if ($value['good_id']) {
                $good_info                         = GoodsModel::get($value['good_id']);
                $result['list'][$key]['good_name'] = $good_info['title'];
                $result['list'][$key]['good_img']  = $good_info['img'];
            }

        }
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
