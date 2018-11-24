<?php
namespace app\khj2\controller;

use app\khj2\model\DailyTask as DailyTaskModel;
use app\khj2\model\DailyTaskRecord as DailyTaskRecordModel;
use app\khj2\model\User as UserModel;
use controller\BasicAdmin;

class DailyTaskRecord extends BasicAdmin
{

    public function index()
    {
        $this->title    = '任务记录';
        list($get, $db) = [$this->request->get(), new DailyTaskRecordModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        foreach ($result['list'] as $key => $value) {
            //用户信息
            $user_info                        = UserModel::where('id', $value['user_id'])->find();
            $result['list'][$key]['nickname'] = isset($user_info['nickname']) ? $user_info['nickname'] : '匿名';
            $result['list'][$key]['avatar']   = isset($user_info['avatar']) ? $user_info['avatar'] : '无';
        }

        $this->assign('title', $this->title);

        $this->task_list();

        return $this->fetch('index', $result);
    }

    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            if (DailyTaskRecordModel::where('id', $data['id'])->delete()) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    private function task_list()
    {
        $data = DailyTaskModel::column('title', 'id');
        $this->assign('task_list', $data);
    }
}
