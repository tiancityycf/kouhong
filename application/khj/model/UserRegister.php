<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj\model;

use think\Model;
use think\Exception;
use think\Db;
use app\khj\model\UserRecord as User;

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
            //判断签到的天数  当签到天数为第7天或以上将按照第7天增加
            if ($user_register_last) {
                if ($user_register_last['nowday'] == 7) {
                    $data['nowday'] = 7;
                    $data['gold'] = $regconfig[7]['gold'];
                    $data['money'] = $regconfig[7]['money'];
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
            //签到逻辑暂时不增加现金，这里屏蔽且强制给0；
            //$money_array = json_decode($data['money']);
            //$data['money'] = rand($money_array[0] * 100, $money_array[1] * 100) / 100;
            $data['money'] = 0;
            $data['create_time'] = date('Y-m-d H:i:s');
            $status = $this->save($data);
            
            if ($status) {
                Db::name('user_record')->where('user_id',$user_id)->setInc('gold',$data['gold']);
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

    /**
     * 判断签到的第几天
     * @param type $user_id
     */
    public function count_days($user_id){
        $last_date = date('ymd', strtotime("-1 day"));
        $user_register_last = $this->get(['user_id' => $user_id, 'add_date' => $last_date]);
        $day = $user_register_last ? $user_register_last['nowday'] + 1 : 1;
        if($day >= 7){
            $day = 7;
        }
        $gold = Db("user_register_config")->where('day',$day)->value('gold');
        $res['count_days'] = $day;
        $res['gold'] = $gold;

        return $res;
    }

}
