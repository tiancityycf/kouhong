<?php

namespace api_data_service\v1_0_1;

use think\Db;
use think\Loader;
use zhise\HttpClient;
use think\facade\Config;
use think\facade\Cache;
use model\User as UserModel;
use model\WithdrawLog as WithdrawLogModel;
use api_data_service\Config as ConfigService;
use model\UserRecord as UserRecordModel;
use model\RedpacketLog as RedpacketLogModel;

use api_data_service\Notify as NotifyService;

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

        $first_withdraw_success_num = ConfigService::get('first_withdraw_success_num');
        $first_withdraw_limit = ConfigService::get('first_withdraw_limit');
        $withdraw_limit = $record['success_num'] > $first_withdraw_success_num ? ConfigService::get('withdraw_limit') : $first_withdraw_limit;

        $heimingdan_config = ConfigService::get('heimingdan_in_off');
        $zongheimingdan_config = config('heimingdan_zongkaiguan');

        if (!$zongheimingdan_config || !$heimingdan_config) {
            $record['user_status'] = 1;
        } 

        return [
            'record' => $record,
            'redpacket_list' => $redpacketList,
            'withdraw_limit' => $withdraw_limit,
            'wen_xin_ti_shi' => ConfigService::get('wen_xin_ti_shi'),
        ];
    }

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

            $heimingdan_config = ConfigService::get('heimingdan_in_off');
            $zongheimingdan_config = config('heimingdan_zongkaiguan');

            if ($zongheimingdan_config && $heimingdan_config) {
                $user_status = $user->userRecord->user_status;
            } else {
                $user_status = 1;
            }


            $result = [
                'status' => 1,
                'user_id' => $user->id,
                'last_login' => $time,
                'chance_num' => $user->userRecord->chance_num,
                'tiaozhuan_num' => $user->userRecord->tiaozhuan_num,
                'user_status' => $user_status,
            ];
        } else {
            trace($data,'error');
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
        // 开启事务
        Db::startTrans();
        try {
            $userRecord = UserRecordModel::where('user_id', $data['user_id'])->lock(true)->find();
            $withdraw_limit = $userRecord->success_num > ConfigService::get('first_withdraw_success_num') ? ConfigService::get('withdraw_limit') : ConfigService::get('first_withdraw_limit');
            if ($userRecord['amount'] < $withdraw_limit) {
                return ['status' => 0, 'msg' => '您的余额不足以提现'];
            }

            if ($data['amount'] > 0 && $userRecord->amount >= $data['amount']) {
                $params = [
                    'appid' => Config::get('wx_appid'),
                    'user_id' => $data['user_id'],
                    'open_id' => '',
                    'amount' => $data['amount'],
                ];

                $params['sign'] = NotifyService::generateSign($params);
                $result = HttpClient::post(Config::get('withdraw_url'), $params);

                if ($result['status'] === 200 && $result['data']['data']['trade_no']) {
                    $userRecord->amount -= $data['amount'];
                    $userRecord->save();

                    WithdrawLogModel::create([
                        'trade_no' => $result['data']['data']['trade_no'],
                        'user_id' => $data['user_id'],
                        'amount' => $data['amount'],
                        'create_time' => time(),
                        'status' => 0, // 提现中
                    ]);

                    Db::commit();

                    return ['status' => 1, 'msg' => '提现申请成功', 'trade_no' => $result['data']['data']['trade_no']];
                    
                }
            }
            return ['status' => 0];
        } catch (\Exception $e) {
            Db::rollback();
            trace($e->getMessage(),'error');
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
}