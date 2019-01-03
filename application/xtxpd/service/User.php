<?php

namespace app\xtxpd\service;

use think\Db;
use think\facade\Config;
use app\xtxpd\model\User as UserModel;

/**
 * 用户服务类
 */
class User
{
    /**
     * 用户登录
     * @return array
     */
    public function login($code,$from_type = 0)
    {
        $appid = Config::get('wx_appid');
        $secret = Config::get('wx_secret');
        $loginUrl = Config::get('wx_login_url');

        try{
            $data = json_decode(file_get_contents(sprintf($loginUrl, $appid, $secret, $code)), true);
        } catch (\Exception $e) {
            lg($e);
            $result = ['status' => 0];
            return $result;
        }

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
                } else {
                    $user = new UserModel();
                    $user->openid = $data['openid'];
                    $user->create_time = $time;
                    $user->session_key = $data['session_key'];
                    $user->save();
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                lg($e);
                $result = ['status' => 0];
                return $result;
            }

            $result = [
                'status' => 1,
                'user_id' => $user->id,
                'last_login' => $time,
                'openid' => $data['openid'],
                'user_status' => 1,
            ];
        } else {
            trace("login error ".json_encode($data),'error');
            $result = ['status' => 0];
        }

        return $result;
    }
}