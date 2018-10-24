<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\qmxz\controller\api\v1_0_1;

use think\facade\Request;
use controller\BasicController;
use app\qmxz\model\Task as TaskModel;
use app\qmxz\model\UserRecord as UserRecord;
use think\Db;
use app\qmxz\model\UserRegister;
use app\qmxz\model\InviteUser;

/**
 * Description of Task
 *
 * @author 157900869@qq.com
 */
class Task extends BasicController {

    //任务邀请好友数量
    protected $task_invite_number = 2;

    public function center() {
        $model = new TaskModel();
        $openid = Request::param('openid');
        $list = $model->getTaskList();
        $day = date("Ymd");
        $issend = Db::name("task_log")->where(['openid' => $openid, 'day' => $day])->column('type,openid,gold,day');
        $usermodel = new UserRecord();
        $userinfo = $usermodel->get(['openid' => $openid]);
        $regmodel = new UserRegister();
        $invitemodel = new InviteUser();
        foreach ($list as $key => $val) {
            //判断有没有领取过
            if (isset($issend[$val['id']]) && $issend[$val['id']]) {
                $list[$key]['is_give'] = 1;
            } else {
                $list[$key]['is_give'] = 0;
            }
            //判断是否签到
            if ($val['id'] == 1) {
                $is_reg = $regmodel->get(['user_id' => $userinfo['user_id']]);
                $list[$key]['is_give'] = $is_reg ? 1 : 0;
            }
            //判断今日邀请好友数量2
            if ($val['id'] == 3) {
                $invite_number = $invitemodel->count(['openid' => $openid, 'create_time' => ['between', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59']]]);
                $list[$key]['invite_number'] = $invite_number;
            }
        }
    }

    //记录分享奖励
    public function share() {
        $openid = Request::param('openid');
        $model = new TaskModel();
        $data = $model->sendTask($openid, 2);
        return json_encode($data);
    }

    //普通场押宝
    public function game_one() {
        $openid = Request::param('openid');
        $model = new TaskModel();
        $data = $model->sendTask($openid, 4);
        return json_encode($data);
    }

    //整点挑战赛
    public function game_top() {
        $openid = Request::param('openid');
        $model = new TaskModel();
        $data = $model->sendTask($openid, 5);
        return json_encode($data);
    }

    //签到
    public function register() {
        $model = new UserRegister();
        $openid = Request::param('openid');
        $data = $model->remark($openid);
        return json_encode($data);
    }

    //记录邀请
    public function invite_user() {
        $task = new TaskModel();
        //分享人openid
        $from_user = Request::param('from_openid');
        //点击人openid
        $openid = Request::param('openid');
        $model = new InviteUser();
        $data = $model->remark($from_user, $openid);
        $invite_number = $model->getInviteDayCount($from_user, date('Y-m-d'));
        if ($invite_number == $this->task_invite_number && $data['code']==0) {
            $task->sendTask($from_user, 3);
        }
        return json_encode($data);
    }

}
