<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj\controller\api\v1_0_1;

use app\khj\model\InviteUser;
use app\khj\model\Task as TaskModel;
use app\khj\model\UserRecord as UserRecord;
use app\khj\model\UserRegister;
use controller\BasicController;
use think\Db;
use think\facade\Request;

/**
 * Description of Task
 *
 * @author 157900869@qq.com
 */
class Task extends BasicController
{

    //任务邀请好友数量
    protected $task_invite_number = 2;

    public function __construct()
    {
        parent::__construct();
        $config_data              = $this->configData;
        $this->task_invite_number = $config_data['task_invite_number'];
    }

    public function center()
    {
        $model       = new TaskModel();
        $openid      = Request::param('openid');
        $list        = $model->getTaskList();
        $day         = date("Ymd");
        $issend      = Db::name("task_log")->where(['openid' => $openid, 'day' => $day])->column('type_id,openid,gold,day');
        $usermodel   = new UserRecord();
        $userinfo    = $usermodel->get(['openid' => $openid]);
        $user_id     = $userinfo['user_id'];
        $regmodel    = new UserRegister();
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
                $is_reg                = $regmodel->get(['user_id' => $user_id, 'add_date' => date('ymd')]);
                $list[$key]['is_give'] = $is_reg ? 1 : 0;
                //判断1.签到的第几天,2.展示可以得到的金币数
                $res                      = $regmodel->count_days($user_id);
                $list[$key]['gold']       = $res['gold'];
                $list[$key]['count_days'] = $res['count_days'];
            }
            //判断今日邀请好友数量
            if ($val['id'] == 3) {
                // $invite_number                    = $invitemodel->where(['openid' => $openid])->where('create_time', 'between', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])->count();
                $invite_number                    = Db::name('invite_user')->where(['openid' => $openid])->where('create_time', 'between', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])->count();
                $list[$key]['task_invite_number'] = $this->task_invite_number;
                $list[$key]['invite_number']      = $invite_number;
                if($invite_number >= $this->task_invite_number){
                    $list[$key]['is_give'] = 1;
                }
            }
            //普通场押宝
            if ($val['id'] == 4) {
                $is_play_topic         = Db::name('user_topic')->where(['user_id' => $user_id, 'create_date' => date('ymd')])->find();
                $list[$key]['is_give'] = $is_play_topic ? 1 : 0;
            }
            //普通场押宝
            if ($val['id'] == 5) {
                $is_play_special       = Db::name('user_special')->where(['user_id' => $user_id, 'create_date' => date('ymd')])->find();
                $list[$key]['is_give'] = $is_play_special ? 1 : 0;
            }

            //反悔卡
            if ($val['id'] == 6) {
                //反悔卡每日获取数量与邀请好友数量一致
                $regret_times                    = Db::name("regret_card")->where(['openid' => $openid])->where('add_date', date('ymd'))->value('times');
                $list[$key]['regret_times']      = isset($regret_times) ? $regret_times : 0;
                $list[$key]['task_regret_times'] = $this->task_invite_number;
            }

        }

        //签到列表
        $sign_list = Db::name('user_register_config')->select();

        //用户当前金币
        $gold = Db::name('user_record')->where(['openid' => $openid])->field('gold')->find()['gold'];

        $res = [
            'gold'      => $gold,
            'sign_list' => $sign_list,
            'list'      => $list,
        ];

        return result('200', "ok", $res);
    }

    //记录分享奖励
    public function share()
    {
        $openid = Request::param('openid');
        $model  = new TaskModel();
        $data   = $model->sendTask($openid, 2);
        return result('200', "ok", $data);
    }

    //普通场押宝
    public function game_one()
    {
        $request       = Request::param();
        $data          = [];
        $is_play_topic = Db::name('user_topic')->where(['user_id' => $request['user_id'], 'create_date' => date('ymd')])->find();
        if ($is_play_topic) {
            $model = new TaskModel();
            $data  = $model->sendTask($request['openid'], 4);
        }
        return result('200', "ok", $data);
    }

    //整点挑战赛
    public function game_top()
    {
        $request         = Request::param();
        $data            = [];
        $is_play_special = Db::name('user_special')->where(['user_id' => $request['user_id'], 'create_date' => date('ymd')])->find();
        if ($is_play_special) {
            $model = new TaskModel();
            $data  = $model->sendTask($request['openid'], 5);
        }
        return result('200', "ok", $data);
    }

    //签到
    public function register()
    {
        $model  = new UserRegister();
        $openid = Request::param('openid');
        $data   = $model->remark($openid);
        return result('200', "ok", $data);
    }

    //记录邀请
    public function invite_user()
    {

        $task = new TaskModel();
        //点击人openid
        $from_user = Request::param('from_openid');
        //分享人openid
        $openid        = Request::param('openid');
        $model         = new InviteUser();
        $data          = $model->remark($openid, $from_user);
        $invite_number = $model->getInviteDayCount($openid, date('Y-m-d'));
        if ($invite_number == $this->task_invite_number && $data['code'] == 0) {
            $task->sendTask($openid, 3);
            //增加完成反悔卡的任务日志
            $task->sendTask($openid, 6);
        }
        return result('200', "ok", $data);
    }

}
