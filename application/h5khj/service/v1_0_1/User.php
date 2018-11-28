<?php

namespace app\h5khj\service\v1_0_1;

use api_data_service\Notify as NotifyService;
use app\h5khj\model\User as UserModel;
use app\h5khj\model\UserRecord as UserRecordModel;
use model\UserRelationList as UserRelationListModel;
use model\WithdrawLog as WithdrawLogModel;
use think\Db;
use think\facade\Cache;
use think\facade\Config;
use Wcs\Http\PutPolicy;
use Wcs\Upload\Uploader;
use zhise\HttpClient;
use think\Controller;

/**
 * 用户服务类
 */
class User extends Controller
{

    public function index($data)
    {
        $user_info = UserRecordModel::where('user_id', $data['user_id'])->field('avatar,nickname,gold,money,qr_img')->find();
        if (!$user_info) {
            $result = [
                'status' => 0,
                'msg'    => '不存在该用户',
                'data'   => '',
            ];
        }
        $where              = [];
        $where['user_id']   = $data['user_id'];
        $where['successed'] = 1;
        $user_info['count'] = Db::name('challenge_log')->where($where)->count();
        $user_info['limit'] = isset($this->configData['success_num']) ? $this->configData['success_num'] : 0;
        if (!isset($user_info['qr_img']) || $user_info['qr_img'] == '') {
            $login_url = Config::get('login_domain') . '?user_id=' . $data['user_id'];
            //生成二维码
            $img_data = createQr($login_url);
            //保存二维码到云存储
            $rt = $this->Upload($img_data['img_url'], $img_data['filepath']);
            if ($rt['code'] == 1) {
                $user_info['qr_img'] = $rt['img_url'];
                //保存二维码图片
                // 开启事务
                Db::startTrans();
                try {
                    $user_record         = UserRecordModel::where('user_id', $data['user_id'])->find();
                    $user_record->qr_img = $rt['img_url'];
                    $user_record->save();
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    lg($e);
                }
                $result = [
                    'status' => 1,
                    'msg'    => 'ok',
                    'data'   => [
                        'user_info' => $user_info,
                    ],
                ];
            } else {
                $result = [
                    'status' => 0,
                    'msg'    => 'fail',
                    'data'   => '',
                ];
            }
        } else {
            $result = [
                'status' => 1,
                'msg'    => 'ok',
                'data'   => [
                    'user_info' => $user_info,
                ],
            ];
        }
        return $result;
    }

    /**
     * 把远程图片上传到另一个站点
     * @return [type] [description]
     */
    private function Upload($img = '', $local_path = '')
    {
        if ($img == '') {
            return ['error_msg' => '地址为空', 'code' => 2];
        }
        $suffix_name = substr(strrchr($img, '.'), 1);
        $time        = time();
        $rand_str    = md5(date('YmdHis', $time) . mt_rand(0, 9999));

        $file_name  = $rand_str . '.' . $suffix_name;
        $bucketName = config('bucketName'); //bucketName 空间名称
        $fileKey    = 'h5khj/' . $file_name; //fileKey   自定义文件名
        $localFile  = '.' . $local_path; //localFile 上传文件名
        $returnBody = ''; //returnBody    自定义返回内容  (可选）
        $userParam  = ''; //userParam 自定义变量名    <x:VariableName>    (可选）
        $userVars   = ''; //userVars  自定义变量值    <x:VariableValue>   (可选）
        $mimeType   = ''; //mimeType  自定义上传类型  (可选）

        $pp = new PutPolicy();
        if ($fileKey == null || $fileKey === '') {
            $pp->scope = $bucketName;
        } else {
            $pp->scope = $bucketName . ':' . $fileKey;
        }
        $pp->returnBody = '';
        $pp->deadline   = ''; //单位为毫秒
        $token          = $pp->get_token();

        $client = new Uploader($token, $userParam, $userVars, $mimeType);
        $resp   = $client->upload_return($localFile);

        if ($resp->code == 200) {
            unlink($localFile);
            $img_url = config('img_url_config') . $fileKey; //详细访问地址
            return ['img_url' => $img_url, 'code' => 1];
        }
        return ['error_msg' => $resp->respBody, 'code' => 2];
    }

    /**
     * 用户登录
     * @return array
     */
    public function login($data)
    {
        //授权登录
        $wx_appid         = Config::get('wx_appid');
        $wx_secret        = Config::get('wx_secret');
        $wx_authorize_url = Config::get('wx_authorize_url');
        //微信access_token获取接口
        $get_access_url = Config::get('get_access_url');
        //微信拉取用户信息接口
        $wx_user_info_url = Config::get('wx_user_info_url');
        //跳转地址
        $redirect_uri = urlencode(Config::get('login_domain'));
        if (isset($data['pid']) && $data['pid'] != '') {
            $state = $data['pid'];
        } else {
            $state = '';
        }
        //判断code是否存在
        if (isset($data['code']) && $data['code'] != '') {
            //获取access_token
            $access_data = json_decode(file_get_contents(sprintf($get_access_url, $wx_appid, $wx_secret, $data['code'])), true);
            if (!isset($access_data['errcode'])) {
                //判断用户信息是否存在
                $user_info = UserModel::where('openid', $access_data['openid'])->find();
                if ($user_info) {
                    $time   = time();
                    $record = UserRecordModel::where('openid', $access_data['openid'])->find();
                    $result = [
                        'status'      => 1,
                        'user_id'     => $user_info->id,
                        'last_login'  => $time,
                        'openid'      => $record['openid'],
                        'user_status' => 1,
                        'money'       => $record["money"],
                    ];
                } else {
                    //拉取用户信息
                    $wx_user_info = json_decode(file_get_contents(sprintf($wx_user_info_url, $access_data['access_token'], $access_data['openid'])), true);
                    if (isset($wx_user_info['errcode']) && $wx_user_info['errcode'] != '') {
                        trace($wx_user_info['errcode'] . $wx_user_info['errmsg'], 'error');
                        $result = [
                            'status' => 0,
                            'msg'    => $wx_user_info['errmsg'],
                            'data'   => '',
                        ];
                    }
                    // 开启事务
                    Db::startTrans();
                    try {
                        //添加用户信息
                        $time              = time();
                        $user              = new UserModel();
                        $user->openid      = $wx_user_info['openid'];
                        $user->nickname    = $wx_user_info['nickname'];
                        $user->avatar      = $wx_user_info['headimgurl'];
                        $user->gender      = $wx_user_info['sex'];
                        $user->create_time = $time;
                        $user->session_key = '';
                        $user->save();
                        //新用户初始化金币的值
                        $userRecord              = new UserRecordModel();
                        $userRecord->user_id     = $user->id;
                        $userRecord->openid      = $wx_user_info['openid'];
                        $userRecord->nickname    = $wx_user_info['nickname'];
                        $userRecord->avatar      = $wx_user_info['headimgurl'];
                        $userRecord->gender      = $wx_user_info['sex'];
                        $userRecord->gold        = 0;
                        $userRecord->last_login  = $time;
                        $userRecord->user_status = 1;
                        $userRecord->save();
                        //判断是否邀请关联
                        if (isset($state) && $state != '') {
                            $user_relation_list = UserRelationListModel::where('user_id', $user->id)->where('pid', $state)->find();
                            if (!$user_relation_list) {
                                $user_relation_list          = new UserRelationListModel();
                                $user_relation_list->pid     = $state;
                                $user_relation_list->user_id = $user->id;
                                $user_relation_list->save();
                            }
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        lg($e);
                    }
                    $record = UserRecordModel::where('user_id', $user->id)->find();
                    $result = [
                        'status'      => 1,
                        'user_id'     => $user->id,
                        'last_login'  => $time,
                        'openid'      => $record['openid'],
                        'user_status' => 1,
                        'money'       => $record["money"],
                    ];
                }
            } else {
                if ($access_data['errcode'] == 40029) {
                    $this->redirect(sprintf($wx_authorize_url, $wx_appid, $redirect_uri, $state));
                    exit;
                }
                trace($access_data['errcode'] . $access_data['errmsg'], 'error');
                $result = [
                    'status' => 0,
                    'msg'    => $access_data['errmsg'],
                    'data'   => '',
                ];
            }
        } else {
            $this->redirect(sprintf($wx_authorize_url, $wx_appid, $redirect_uri, $state));
            exit;
        }
        return $result;
    }

    /**
     * 更新用户信息
     * @return void
     */
    // public function update($data)
    // {
    //     // 开启事务
    //     Db::startTrans();
    //     try {
    //         $time      = time();
    //         $userModel = new UserModel();
    //         $user      = $userModel->where('openid', $data['openid'])->find();
    //         if (empty($user)) {
    //             Db::rollback();
    //             trace($userModel->getLastSql(), 'error');
    //             return ['error' => '用户不存在'];
    //         }
    //         //dump($user);die;
    //         $user->nickname                = $data['nickname'];
    //         $user->avatar                  = $data['avatar'];
    //         $user->gender                  = $data['gender'];
    //         $user->update_time             = $time;
    //         $user->userRecord->nickname    = $data['nickname'];
    //         $user->userRecord->avatar      = $data['avatar'];
    //         $user->userRecord->update_time = $time;
    //         $user->userRecord->gender      = $data['gender'];

    //         $user->save();
    //         $user->userRecord->save();

    //         Db::commit();

    //         $user_status = $user->userRecord->user_status;

    //         return ['user_status' => $user_status];
    //     } catch (\Exception $e) {
    //         Db::rollback();
    //         lg($e);
    //         return ['error' => $e->getMessage()];
    //     }
    // }

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

            if ($userRecord['dis_money'] < $withdraw_limit) {
                return ['status' => 0, 'msg' => '您的余额不足以提现'];
            }

            if ($data['amount'] > 0 && $userRecord->dis_money >= $data['amount']) {
                if ($data['type'] == 1) {
                    $params = [
                        'appid'   => Config::get('wx_appid'),
                        'user_id' => $data['user_id'],
                        'open_id' => '',
                        'amount'  => $data['amount'],
                    ];
                    $params['sign'] = NotifyService::generateSign($params);
                    $result         = HttpClient::post(Config::get('withdraw_url'), $params);
                    if ($result['status'] === 200 && $result['data']['data']['trade_no']) {
                        $userRecord->dis_money = ['dec', $data['amount']];
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
                if ($data['type'] == 2) {
                    $userRecord->money       = ['inc', $data['amount']];
                    $userRecord->total_money = ['inc', $data['amount']];
                    $userRecord->dis_money   = ['dec', $data['amount']];
                    $userRecord->save();
                    Db::commit();
                    return ['status' => 1, 'msg' => '提现成功'];
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
}
