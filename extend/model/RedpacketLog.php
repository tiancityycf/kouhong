<?php

namespace model;

use think\Model;

/**
 * 红包日志模型类
 */
class RedpacketLog extends Model
{
    /**
     * 获取红包记录
     * @param  integer $userId 用户id
     * @return array
     */
    public function getRedpacketList($userId)
    {
        $receiveList = self::where('user_id', $userId)->order('id desc')->select();
        $result = [];
        foreach ($receiveList as $key => $receive) {
            $result[$key] = [
                'amount' => $receive['amount'],
                'create_time' => $receive['create_time'],
            ];
        }

        return $result;
    }


    public function getNewList($user_id)
    {
        $date = date('ymd',time());
        $lists = self::where('user_id', $user_id)->where('create_date',$date)->order('status asc')->order('id desc')->select();

        $list_data = [];
        $i = 0;
        if ($lists) {
            foreach ($lists as $list) {
                $list_data[$i]['redpacket_id'] = $list['id'];
                $list_data[$i]['redpacket_status'] = $list['status'];
                if ($list->status == 1) {
                    $list_data[$i]['amount'] = $list['amount'];
                } else {
                    $list_data[$i]['amount'] = 0;
                }
                $list_data[$i]['create_time'] = $list['create_time'];
                $i ++; 
            }
        }

        $old_lists = self::where('user_id', $user_id)->where('create_date','<',$date)->where('status',1)->order('id desc')->select();

        if ($old_lists) {
            foreach ($old_lists as $old_list) {
                $list_data[$i]['redpacket_id'] = $list['id'];;
                $list_data[$i]['redpacket_status'] = $list['status'];
                $list_data[$i]['amount'] = $list['amount'];
                $list_data[$i]['create_time'] = $list['create_time'];
                $i ++; 
            }
        }

        return $list_data;
    }
}