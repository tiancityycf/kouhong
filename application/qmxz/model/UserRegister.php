<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\qmxz\model;

use think\Model;
use think\Exception;
use think\Db;
use app\qmxz\model\UserRecord as User;

/**
 * Description of UserRegister
 *
 * @author 157900869@qq.com
 */
class UserRegister extends Model {
    
    //签到
    public function remark($openid) {
        if (!$openid) {
            return ['code' => 10003, 'data' => '', 'message' => '缺少用户ID'];
        }
        $ModelUser = new User();
        $userinfo = $ModelUser->get(['openid' => $openid]);
        $user_id = $userinfo['user_id'];
        $date = date('ymd');
        $last_date = date('ymd', strtotime("-1 day"));
        $data['user_id'] = $user_id;
        $data['add_date'] = $date;

        $user_register = $this->get(['user_id' => $user_id, 'add_date' => $date]);
        //判断是否已经签到
        if (!$user_register) {
            $user_register_last = $this->get(['user_id' => $user_id, 'add_date' => $last_date]);
            $day = $user_register_last ? $user_register_last['nowday'] + 1 : 1;
            $regcofiglist = Db("user_register_config")->select();
            $regconfig = [];
            foreach ($regcofiglist as $key => $value) {
                $regconfig[$value['day']] = $value;
            }
            $type = 0;
            //判断签到的天数
            if ($user_register_last) {
                if ($user_register_last['nowday'] == 7) {
                    $data['nowday'] = 1;
                    $data['gold'] = $regconfig[1]['gold'];
                    $data['money'] = $regconfig[1]['money'];
                } else {
                    $data['gold'] = $regconfig[$day]['gold'];
                    $data['nowday'] = $day;
                    $data['money'] = $regconfig[$day]['money'];
                }
            } else {
                $data['gold'] = $regconfig[$day]['gold'];
                $data['nowday'] = $day;
                $data['money'] = $regconfig[$day]['money'];
            }
            $money_array = json_decode($data['money']);
            $data['money'] = rand($money_array[0] * 100, $money_array[1] * 100) / 100;
            $data['create_time'] = date('Y-m-d H:i:s');
            $status = $this->save($data);
            if ($status) {
                $ModelUser->save(['gold' => ['exp', 'gold+' . $data['gold']], 'money' => ['exp', 'money+' . $data['money']], 'total_money' => ['exp', 'total_money+' . $data['money']], 'total_gold' => ['exp', 'total_gold+' . $data['gold']]], ['user_id' => $user_id]);
            }
            $msgs['code'] = 0;
            $msgs['data'] = ['day' => $day, 'gold' => $data['gold'], 'money' => $data['money'], 'type' => $type];
            $msgs['msg'] = '签到成功';
        } else {
            $msgs['code'] = 1;
            $msgs['data'] = 0;
            $msgs['msg'] = '今日已签到';
        }
        return $msgs;
    }
    /**
     * 是否已经签到
     * @param type $user_id
     */
    public function isreg($user_id){
         $date = date('ymd');
         $user_register = $this->get(['user_id' => $user_id, 'add_date' => $date]);
         return $user_register;
    }

}
