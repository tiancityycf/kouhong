<?php

namespace app\h5khj\service\v1_0_1;

use api_data_service\Notify as NotifyService;
use app\h5khj\model\User as UserModel;
use app\h5khj\model\UserRecord as UserRecordModel;
use app\h5khj\model\UserRelationRecord as UserRelationRecordModel;
use model\WithdrawLog as WithdrawLogModel;
use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Config;
use Wcs\Http\PutPolicy;
use Wcs\Upload\Uploader;
use zhise\HttpClient;

/**
 * 用户服务类
 */
class User extends Controller
{
    public function index($data)
    {
        //新用户初始化金币的值
        $config         = Cache::get(config('config_key'));
        $withdraw_limit = $config['withdraw_limit']['value'];
        $user_info      = UserRecordModel::where('user_id', $data['user_id'])->field('user_id,avatar,nickname,money,qr_img,dis_money')->find();
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
                //生成海报
                $config = array(
                    'image'      => array(
                        array(
                            'url'     => $rt['img_url'],
                            'stream'  => 0,
                            'left'    => 169,
                            'top'     => -90,
                            'right'   => 0,
                            'bottom'  => 0,
                            'width'   => 300,
                            'height'  => 300,
                            'opacity' => 100,
                        ),
                    ),
                    'background' => "https://txcdn.ylll111.xyz/khjhfive/e30f92cee3b750681a2e389e770bcf6d.jpg", //背景图
                );
                $filename            = "./static/upload/teset" . $user_info['user_id'] . ".jpg";
                $img_path            = createPoster($config, $filename);
                $img_path            = ltrim($img_path, '.');
                $img_arr             = $this->Upload($img_path, $img_path);
                $img_url             = $img_arr['img_url'];
                $user_info['qr_img'] = $img_url;
                //保存二维码图片
                // 开启事务
                Db::startTrans();
                try {
                    $user_record = UserRecordModel::where('user_id', $data['user_id'])->find();
                    if ($user_record) {
                        $user_record->qr_img = $img_url;
                        $user_record->save();
                        Db::commit();
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                    lg($e);
                }
                $result = [
                    'status' => 1,
                    'msg'    => 'ok',
                    'data'   => [
                        'withdraw_limit' => $withdraw_limit,
                        'user_info'      => $user_info,
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
                    'withdraw_limit' => $withdraw_limit,
                    'user_info'      => $user_info,
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
        //var_dump($localFile);exit;
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

    /**
     * 用户佣金记录
     * @param  integer $data 接收数据
     * @return array
     */
    public function userRelationList($data)
    {
        $list = UserRelationRecordModel::where('pid', $data['user_id'])->order('addtime desc')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $user_info                   = UserModel::where('id', $value['user_id'])->find();
                $list[$key]['user_nickname'] = isset($user_info['nickname']) ? $user_info['nickname'] : '';
                $list[$key]['user_avatar']   = isset($user_info['avatar']) ? $user_info['avatar'] : '';
            }
        }
        return $list;
    }

    public function saveCode($data)
    {
        $userRecord = UserRecordModel::where('user_id', $data['user_id'])->find();

        if (!$userRecord || $data['img_content'] == '') {
            return ['status' => 0, 'msg' => '系统错误'];
        }

        if ($userRecord->qr_path != '') {
            return ['status' => 2, 'msg' => '图片已经存在', 'path' => $userRecord->qr_path];
        }

        $local_path = base64_image_content($data['img_content']);

        $local_path = ltrim($local_path, '.');

        $img_arr = $this->Upload($local_path, $local_path);

        if ($img_arr['code'] == 1) {
            $userRecord->qr_path = $img_arr['img_url'];
            $userRecord->save();

            return ['status' => 1, 'msg' => '已经保存', 'path' => $img_arr['img_url']];
        } else {
            return ['status' => 3, 'msg' => '上传错误'];
        }

    }
}
