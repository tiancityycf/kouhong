<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\qmxz\model;

use think\Model;
use think\Exception;

/**
 * Description of Task
 *
 * @author 157900869@qq.com
 */
class Task extends Model {

    protected $gold_field = 'gold';
    protected $total_gold_field = 'total_gold';

    /**
     * 获取任务列表
     */
    public function getTaskList() {
        $list = $this->db('task')->field('id,title,des,gold,img_url,status,express,btn_type,btn_text')->select();
        return $list;
    }

    /**
     * 记录任务日志
     * @param type $openid
     * @param type $task_id
     */
    public function sendTask($openid, $task_id) {

        $info = $this->db('task')->where(['id' => $task_id])->field('id,title,des,gold,img_url,status')->find();
        if (!$info) {
            return ['status' => 0, 'info' => '没有找到相关配置'];
        }
        if (!$info['status']==0) {
            return ['status' => 0, 'info' => '奖励配置已关闭'];
        }
        $day = date("Ymd");
        if($info['express']==0){
           $issend = $this->db('task_log')->where(['openid' => $openid, 'day' => $day])->find(); 
        }else if($info['express']==1){
           $issend = $this->db('task_log')->where(['openid' => $openid])->find(); 
        }
        if ($issend) {
            return ['status' => 0, 'info' => '您已经领取过该奖励了'];
        }
        try {
            $data['openid'] = $openid;
            $data['day'] = $day;
            $data['create_time'] = time();
            $data['gold'] = $info['gold'];
            $data['type_id'] = $task_id;
            $inid = $this->db('task_log')->insertGetId($data);
            if ($inid) {
                $this->db('user')->setInc($this->gold_field, $info['gold']);
                $this->db('user')->setInc($this->total_gold_field, $info['gold']);
                return ['status'=>200,'info'=>'操作成功','data'=>$info['gold']];
            }else{
                return ['status'=>0,'info'=>'操作失败','data'=>0];
            }
        } catch (Exception $ex) {
             return ['status' => 0, 'info' => '增加金币异常'];
        }
    }

}
