<?php

namespace app\qmxz\model;

use think\Model;
use think\Exception;
use think\Db;
use app\qmxz\model\UserRecord as User;

/**
 * 邀请好友
 *
 * @author 157900869@qq.com
 */
class InviteUser extends Model{
    //重新定义用户表位置
    protected $userTable='user_record';
    /**
     * 记录邀请好友
     * @param type $openid 被邀请人
     * @param type $from_user 邀请人
     */
    public function remark($openid, $from_user) {
        $user = new User();
        if (!$from_user || !$openid) {
            return ['code' => 10003, 'data' => '', 'message' => '缺少用户ID'];
        }
        if ($from_user == $openid) {
            return ['code' => 10042, 'data' => '', 'message' => '邀请人不能使自己	 '];
        }
        $isinvite = $this->get(['openid' => $from_user, 'invite_user' => $openid]);
        if ($isinvite) {
            return ['code' => 10032, 'data' => '', 'message' => '该账号已被邀请'];
        } else {
            $invite_config = $this->getInviteConfig();
            //最后邀请的人
            $liststep = $this->getLastStep($from_user);
            $step = $liststep ? $liststep + 1 : 1;
            //$give_gold = $this->config_cache['invite_user_give_gold'];
            $data['openid'] = $from_user;
            $data['invite_user'] = $openid;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['gold'] = $invite_config[$step]['gold'] ? $invite_config[$step]['gold'] : 0;
            $data['money'] = $invite_config[$step]['money'] ? $invite_config[$step]['money'] : 0;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['is_give'] = 0;
            $data['step'] = isset($invite_config[$step]['gold']) ? $step : 0;
            $status = $this->save($data);
            $id=$this->getData('id');
            if ($status && $id) {
                return $this->give($from_user, $id);
            } else {
                return ['code' => 10033, 'data' => '', 'message' => '邀请记录失败'];
            }
        }
    }

    //赠送金币和红包
    public function give($openid, $id) {
        $data = $this->get(["openid" => $openid, 'id' => $id]);
        if (!$data) {
            return ['code' => 10034, 'data' => '', 'message' => '邀请记录不存在'];
        }
        if ($data['is_give'] == 2) {
            return ['code' => 10035, 'data' => '', 'message' => '记录已被领取'];
        }
        Db::name("InviteUser")->startTrans();
        try {
            $checkid = Db::name("InviteUserCheck")->insertGetId(['invite_id' => $id]);
            $update = Db::name("InviteUser")->where(["openid" => $openid, 'id' => $id])->update(['is_give' => 2, 'give_time' => date('Y-m-d H:i:s')]);
            $updateuser = Db::name($this->userTable)->where(["openid" => $openid])->update([
                        'money' => ['exp', 'money+' . $data['gold']], 'total_money' => ['exp', 'total_money+' . $data['gold']],
                        'gold' => ['exp', 'gold+' . $data['gold']], 'total_gold' => ['exp', 'total_gold+' . $data['gold']]
                        ]);
            if ($checkid && false !== $update && false !== $updateuser) {
                $now = Db::name($this->userTable)->where(["openid" => $openid])->field("gold,money")->find();
                Db::name("InviteUser")->commit();
                return ['code' => 0, 'data' => $now, 'message' => '领取成功'];
            } else {
                Db::name("InviteUser")->rollback();
                return ['code' => 10037, 'data' => '', 'message' => '领取失败'];
            }
        } catch (Exception $exc) {
            Db::name("InviteUser")->rollback();
            return ['code' => 10999, 'data' => '', 'message' => $exc->getMessage()];
        }
    }
    //获取最后的邀请人
    public function getLastStep($openid) {
        $list = Db::name("InviteUser")->connect($this->connection)->where(['openid' => $openid])->max('step');
        return $list;
    }

    //获取邀请奖励配置
    public function getInviteConfig() {
        $list = Db::name("invite_user_config")->connect($this->connection)->order('step asc')->column('step ,gold ,money');
        return $list;
    }
    
    public function getInviteDayCount($openid,$day){
        $count= Db::name("InviteUser")->connect($this->connection)->where(['openid' => $openid])->where('create_time','between', [$day . ' 00:00:00',$day . ' 23:59:59'])->count();

         return $count;
    }

}
