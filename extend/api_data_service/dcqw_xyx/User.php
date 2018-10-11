<?php

namespace api_data_service\dcqw_xyx;

use think\Db;
use think\facade\Config;
use think\facade\Cache;
use model\User as UserModel;
use api_data_service\Config as ConfigService;
use model\UserRecord as UserRecordModel;
/**
 * 用户服务类
 */
class User
{
    /**
     * 用户首页
     * @param  $userId 用户id
     * @return json
     */
    public function index($userId)
    {
        // 获取用户记录
        $userRecord = UserRecordModel::where('user_id', $userId)->find();
        $record = $userRecord->getData();
        return [
            'record' => $record,
        ];
    }

    /**
     * 用户登录
     * @return array
     */
    public function login($code)
    {
        $appid = Config::get('wx_appid');
        $secret = Config::get('wx_secret');
        $loginUrl = Config::get('wx_login_url');

        $data = json_decode(file_get_contents(sprintf($loginUrl, $appid, $secret, $code)), true);

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
                    if (date('Ymd', $user->userRecord->last_login) !== date('Ymd', $time)) {
                        if ($user->userRecord->chance_num < ConfigService::get('login_get_chance_num')) {
                            $user->userRecord->chance_num = ConfigService::get('login_get_chance_num');
                        }
                        $user->userRecord->tiaozhuan_num = 0;
                    }
                    $user->userRecord->last_login = $time;
                    $user->save();
                    $user->userRecord->save();
                } else {
                    $user = new UserModel();
                    $user->openid = $data['openid'];
                    $user->create_time = $time;
                    $user->session_key = $data['session_key'];
                    $user->save();
                    $userRecord = new UserRecordModel();
                    $userRecord->user_id = $user->id;
                    $userRecord->last_login = $time;
                    $userRecord->chance_num = ConfigService::get('new_user_get_chance_num');
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
            $user = UserModel::get($data['user_id']);
            $user->nickname = $data['nickname'];
            $user->avatar = $data['avatar'];
            $user->gender = $data['gender'];
            $user->update_time = $time;
            $user->userRecord->nickname = $data['nickname'];
            $user->userRecord->avatar = $data['avatar'];
            $user->userRecord->update_time = $time;

            $user->save();
            $user->userRecord->save();

            Db::commit();

            return ['status' => 1];
        } catch (\Exception $e) {
            Db::rollback();
            trace($e->getMessage(),'error');
            throw new \Exception("系统繁忙");
        }
    }
}