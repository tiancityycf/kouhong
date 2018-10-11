<?php

namespace api_data_service\v1_0_0;

use think\Db;
use think\Loader;
use zhise\HttpClient;
use think\facade\Config;
use think\facade\Cache;
use model\User as UserModel;

use app\bxdj\model\UserRecord as UserRecordModel;


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
    public function index($openid)
    {

        // 获取用户记录
        $userRecord = UserRecordModel::where('openid', $openid)->find();
        //$record = $userRecord->getData();

        //获取缓存商品信息  商品的库存信息实时更新  不能查询缓存数据
        //$goods_info = Cache::get(config('goods_info'));
        $goods_info = $this->get_product_info();
            
        //获取配置信息
        $config = Cache::get(config('config_key'));

        //获取
        //dump($goods_info);
        //dump($config);die;
        
        return [
            'goods_info' => $goods_info,
            'config' => $config,
            
        ];
    }

    //获取产品信息
    public function get_product_info(){

         $arr = Db::name('goods')->where(['status' => 1, 'onsale' => 1])->column('id, cate, title, img, stock, price', 'id');

            foreach ($arr as $k => $v) {
                $arr[$k]['imgs'] = Db::name('good_imgs')->where('product_id',$v['id'])->select();
            }

            //1.新人换礼 2.邀请专区 3.精品好礼;
            foreach ($arr as $k => $v) {
                if($v['cate'] == 1){
                     $v['cate'] = '新人换礼';
                     $goods_info['xrhl'][] =$v;
                }else if($v['cate'] == 2){
                    $v['cate'] = '邀请专区';
                    $goods_info['yqzq'][] =$v;
                }else{
                    $v['cate'] = '精品好礼';
                    $goods_info['jphl'][] =$v;
                }
            }

            return $goods_info;
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
                    $userRecord->last_login = $time;
                    if ($from_type == 1) {
                        $userRecord->user_status = 2;
                    } else {
                        $userRecord->user_status = 1;
                    }
                   
                    $userRecord->save();
                    //初始化步数
                    Db::name('step_coin')->insert(['openid'=>$data['openid']]);
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
            $withdraw_limit = $userRecord->redpacket_num > (ConfigService::get('first_withdraw_success_num') ? ConfigService::get('first_withdraw_success_num') : 0) ? ConfigService::get('withdraw_limit') : ConfigService::get('first_withdraw_limit');
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