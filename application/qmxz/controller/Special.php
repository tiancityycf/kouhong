<?php

namespace app\qmxz\controller;

use app\qmxz\model\Special as SpecialModel;
use app\qmxz\model\SpecialPrize as SpecialPrizeModel;
use controller\BasicAdmin;
use think\Db;

//整点场控制器类
class Special extends BasicAdmin
{
    public function index()
    {
        $this->title = '整点场管理';

        list($get, $db) = [$this->request->get(), new SpecialModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);

        foreach ($result['list'] as $key => $value) {
            $prize_info                         = Db::name('special_prize')->find($value['prize_id']);
            $result['list'][$key]['prize_name'] = $prize_info['name'];
            $result['list'][$key]['prize_img']  = $prize_info['img'];
            $result['list'][$key]['banners']    = json_decode($value['banners']);
        }
        return $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['banners']      = json_encode($data['banners'], JSON_UNESCAPED_UNICODE);
            $model                = new SpecialModel();
            $data['display_time'] = strtotime($data['display_time']);
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

        $vo        = SpecialModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
            $post_data['banners']      = json_encode($post_data['banners'], JSON_UNESCAPED_UNICODE);
            $post_data['display_time'] = strtotime($post_data['display_time']);

            if ($vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $vo->display_time = date('Y-m-d H:i:s', $vo->display_time);
        $this->prize_list();
        return $this->fetch('edit', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = SpecialModel::get($data['id']);
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
