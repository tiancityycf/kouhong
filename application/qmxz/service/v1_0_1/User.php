<?php

namespace app\qmxz\service\v1_0_1;

use think\Db;
use think\facade\Config;
use app\qmxz\model\User as UserModel;
use app\qmxz\model\UserRecord as UserRecordModel;

/**
 * 用户服务类
 */
class User
{
    /**
     * 用户登录
     * @return array
     */
    public function login($code, $from_type = 0)
    {
        $appid = Config::get('wx_appid');
        $secret = Config::get('wx_secret');
        $loginUrl = Config::get('wx_login_url');
        $data = json_decode(file_get_contents(sprintf($loginUrl, $appid, $secret, $code)), true);
        //强制通过
        //$data['openid'] = 1;
        //$data['session_key'] = 'test';

        $result = [];
        if (isset($data['openid'])) {
            $user = UserModel::where('openid', $data['openid'])->find();

            // 开启事务
            Db::startTrans();
            try {

                $time = time();
                if (!empty($user)) {
                    $user->update_time = $time;
                    $user->session_key = $data['session_key'];
                    
                    $user->save();
                    $user->userRecord->last_login = $time;
                    $user->userRecord->save();

                } else {

                    $user = new UserModel();
                    $user->openid = $data['openid'];
                    $user->create_time = $time;
                    $user->session_key = $data['session_key'];
                    $user->save();

                    $userRecord = new UserRecordModel();
                    $userRecord->user_id = $user->id;
                    $userRecord->openid = $data['openid'];
                    $userRecord->gold = 500;
                    $userRecord->last_login = $time;
                    if ($from_type == 1) {
                        $userRecord->user_status = 2;
                    } else {
                        $userRecord->user_status = 1;
                    }
                   
                    $userRecord->save();
                    
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \Exception("系统繁忙");
            }

            $result = [
                'status' => 1,
                'user_id' => $user->id,
                'last_login' => $time,
                'openid' => $data['openid'],
                'user_status' => 1,
            ];
        } else {
            $result = ['status' => 0];
        }

        return $result;
    }

    /**
     * 更新用户信息
     * @return void
     */
    public function update($data)
    {
        // 开启事务
        Db::startTrans();
        try {
            $time = time();
            $user = UserModel::where('openid', $data['openid'])->find();
            //dump($user);die;
            $user->nickname = $data['nickname'];
            $user->avatar = $data['avatar'];
            $user->gender = $data['gender'];
            $user->update_time = $time;
            $user->userRecord->nickname = $data['nickname'];
            $user->userRecord->avatar = $data['avatar'];
            $user->userRecord->update_time = $time;
            $user->userRecord->gender = $data['gender'];
            
            $user->save();
            $user->userRecord->save();

            Db::commit();

            $user_status = $user->userRecord->user_status;

            return ['user_status' => $user_status];
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception("系统繁忙");
        }
    }
}