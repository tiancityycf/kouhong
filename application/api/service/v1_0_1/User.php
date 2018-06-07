<?php

namespace app\api\service\v1_0_1;

use think\Db;
use think\facade\Config;
use think\facade\Cache;
use app\api\model\User as UserModel;
use app\api\model\WithdrawLog as WithdrawLogModel;
use app\api\service\Config as ConfigService;
use app\api\model\UserRecord as UserRecordModel;
use app\api\model\RedpacketLog as RedpacketLogModel;

use app\api\service\v1_0_1\Trade as TradeService;

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
        $record = UserRecordModel::where('user_id', $userId)->find()->getData();

        // 获取红包记录
        $redpacketLogModel = new RedpacketLogModel();
        $redpacketList = $redpacketLogModel->getRedpacketList($userId);

        return [
            'record' => $record,
            'redpacket_list' => $redpacketList,
            'withdraw_limit' => ConfigService::get('withdraw_limit'),
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
                    $userRecord->chance_num = ConfigService::get('new_user_get_chance_num');
                    $userRecord->save();
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \Exception("系统繁忙");
            }

            $result = ['status' => 1, 'user_id' => $user->id, 'last_login' => $time];
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
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception("系统繁忙");
        }
    }

    /**
     * 取现
     * @param  array $data 请求数据
     * @return boolean
     */
    public function withdraw($data)
    {
        $userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();
        if ($userRecord['amount'] < ConfigService::get('withdraw_limit')) {
            return ['status' => 0, 'msg' => '您的余额不足以提现'];
        }

        if ($data['amount'] > 0 && $userRecord->amount >= $data['amount']) {
            // 开启事务
            Db::startTrans();
            try {
                $userRecord->amount -= $data['amount'];
                $userRecord->save();

                $tradeNo = TradeService::generateTradeNo();

                WithdrawLogModel::create([
                    'trade_no' => $tradeNo,
                    'user_id' => $data['user_id'],
                    'amount' => $data['amount'],
                    'create_time' => time(),
                ]);

                Db::commit();

                return ['status' => 1, 'msg' => '提现成功', 'trade_no' => $tradeNo, 'kefu' => ConfigService::get('weixin_kefu')];
            } catch (\Exception $e) {
                Db::rollback();
                throw new \Exception('系统繁忙');
            }
        } else {
            return ['status' => 0];
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
}