<?php

namespace app\khj\service\v1_0_1;

use api_data_service\Notify as NotifyService;
use app\khj\model\User as UserModel;
use app\khj\model\UserRecord as UserRecordModel;
use model\WithdrawLog as WithdrawLogModel;
use think\Db;
use think\facade\Cache;
use think\facade\Config;
use zhise\HttpClient;

/**
 * 用户服务类
 */
class User
{
    /**
     * 用户登录
     * @return array
     */
    public function login($code, $appid, $from_type = 0)
    {
        if (isset($appid)) {
            $applist = Config::get('applist');
            if (isset($applist[$appid])) {
                $appid  = $appid;
                $secret = $applist[$appid];
            } else {
                $result = ['status' => 0, 'msg' => '不能识别的appid'];
                return $result;
            }
        } else {
            $appid  = Config::get('wx_appid');
            $secret = Config::get('wx_secret');
        }
        $loginUrl = Config::get('wx_login_url');

        try {
            $data = json_decode(file_get_contents(sprintf($loginUrl, $appid, $secret, $code)), true);
        } catch (\Exception $e) {
            lg($e);
            $result = ['status' => 0];
            return $result;
        }

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
                    $user              = new UserModel();
                    $user->openid      = $data['openid'];
                    $user->create_time = $time;
                    $user->session_key = $data['session_key'];
                    $user->save();
                    //新用户初始化金币的值
                    $userRecord             = new UserRecordModel();
                    $userRecord->user_id    = $user->id;
                    $userRecord->openid     = $data['openid'];
                    $userRecord->gold       = 0;
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
                lg($e);
                $result = ['status' => 0];
                return $result;
            }

            $record = UserRecordModel::where('user_id', $user->id)->find();

            $result = [
                'status'      => 1,
                'user_id'     => $user->id,
                'last_login'  => $time,
                'openid'      => $data['openid'],
                'user_status' => 1,
                'money'       => $record["money"],
            ];
        } else {
            trace("login error " . json_encode($data), 'error');
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
            $time      = time();
            $userModel = new UserModel();
            $user      = $userModel->where('openid', $data['openid'])->find();
            if (empty($user)) {
                Db::rollback();
                trace($userModel->getLastSql(), 'error');
                return ['error' => '用户不存在'];
            }
            //dump($user);die;
            $user->nickname                = $data['nickname'];
            $user->avatar                  = $data['avatar'];
            $user->gender                  = $data['gender'];
            $user->update_time             = $time;
            $user->userRecord->nickname    = $data['nickname'];
            $user->userRecord->avatar      = $data['avatar'];
            $user->userRecord->update_time = $time;
            $user->userRecord->gender      = $data['gender'];

            $user->save();
            $user->userRecord->save();

            Db::commit();

            $user_status = $user->userRecord->user_status;

            return ['user_status' => $user_status];
        } catch (\Exception $e) {
            Db::rollback();
            lg($e);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 取现
     * @param  array $data 请求数据
     * @return boolean
     */
    public function withdraw($data)
    {
        // 开启事务
        Db::startTrans();
        try {
            $userRecord = UserRecordModel::where('user_id', $data['user_id'])->lock(true)->find();

            //新用户初始化金币的值
            $config         = Cache::get(config('config_key'));
            $withdraw_limit = $config['withdraw_limit']['value'];

            if ($userRecord['money'] < $withdraw_limit) {
                return ['status' => 0, 'msg' => '您的余额不足以提现'];
            }

            if ($data['amount'] > 0 && $userRecord->money >= $data['amount']) {
                $params = [
                    'appid'   => Config::get('wx_appid'),
                    'user_id' => $data['user_id'],
                    'open_id' => '',
                    'amount'  => $data['amount'],
                ];

                $params['sign'] = NotifyService::generateSign($params);

                $result = HttpClient::post(Config::get('withdraw_url'), $params);

                if ($result['status'] === 200 && $result['data']['data']['trade_no']) {
                    $userRecord->money -= $data['amount'];
                    $userRecord->save();

                    WithdrawLogModel::create([
                        'trade_no'    => $result['data']['data']['trade_no'],
                        'user_id'     => $data['user_id'],
                        'amount'      => $data['amount'],
                        'create_time' => time(),
                        'status'      => 0, // 提现中
                    ]);

                    Db::commit();

                    return ['status' => 1, 'msg' => '提现申请成功', 'trade_no' => $result['data']['data']['trade_no']];

                }
            }
            return ['status' => 0];
        } catch (\Exception $e) {
            Db::rollback();
            trace($e->getMessage(), 'error');
            throw new \Exception('系统繁忙');
        }

    }

    /**
     * 获取提现记录
     * @param  integer $userId 用户id
     * @return array
     */
    public function getWithdrawList($userId)
    {
        $tradeLogModel = new WithdrawLogModel();
        return $tradeLogModel->getWithdrawList($userId);
    }

    /**
     * 测试
     * @return array
     */
    public function test()
    {
        //授权登录
        $wx_appid         = 'wxe1e2993b454a338e';
        $wx_authorize_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=%s#wechat_redirect";
        //跳转地址
        $redirect_uri = urlencode("https://www.baidu.com");
        $state        = '';
        $login_url    = sprintf($wx_authorize_url, $wx_appid, $redirect_uri, $state);
        // var_dump($login_url);exit;
        Header("Location:$login_url");
    }
}
