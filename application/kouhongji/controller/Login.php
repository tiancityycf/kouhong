<?php
namespace app\kouhongji\controller;

use app\h5khj\model\User as UserModel;
use app\h5khj\model\UserRecord as UserRecordModel;
use model\UserRelationList as UserRelationListModel;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Request;

class Login extends controller
{
    public function index()
    {
        $data = Request::param();
        //授权登录
        $wx_appid         = config('wx_appid');
        $wx_secret        = config('wx_secret');
        $wx_authorize_url = config('wx_authorize_url');
        //微信access_token获取接口
        $get_access_url = config('get_access_url');
        //微信拉取用户信息接口
        $wx_user_info_url = config('wx_user_info_url');
        //跳转地址
        $redirect_uri = urlencode(config('login_domain'));
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
                        'user_id'     => $user_info->id,
                        'last_login'  => $time,
                        'openid'      => $record['openid'],
                        'user_status' => 1,
                        'money'       => $record["money"],
                    ];
                    session('uid', $user_info->id);
                    session('last_login', date('Y-m-d H:i:s',$time));
                    session('openid', $record['openid']);
                    session('user_status', 1);
                    session('money', $record["money"]);
                    $this->redirect("index/index");
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
                        'user_id'     => $user->id,
                        'last_login'  => $time,
                        'openid'      => $record['openid'],
                        'user_status' => 1,
                        'money'       => $record["money"],
                    ];
                    session('uid', $user->id);
                    session('last_login', date('Y-m-d H:i:s',$time));
                    session('openid', $record['openid']);
                    session('user_status', 1);
                    session('money', $record["money"]);
                    $this->redirect("index/index");
                }
            } else {
                if ($access_data['errcode'] == 40029) {
                    $this->redirect(sprintf($wx_authorize_url, $wx_appid, $redirect_uri, $state));
                    exit;
                }
                trace($access_data['errcode'] . $access_data['errmsg'], 'error');
                $this->error($access_data['errmsg']);
            }
        } else {
            $this->redirect(sprintf($wx_authorize_url, $wx_appid, $redirect_uri, $state));
            exit;
        }
    }
}
