<?php

namespace app\khj2\service\v1_0_1;

use app\khj2\model\DailyTask as DailyTaskModel;
use app\khj2\model\DailyTaskInvite as DailyTaskInviteModel;
use app\khj2\model\DailyTaskQun as DailyTaskQunModel;
use app\khj2\model\DailyTaskRecord as DailyTaskRecordModel;
use app\khj2\model\User as UserModel;
use app\khj2\model\UserRecord as UserRecordModel;
use think\Db;
use think\facade\Config;

/**
 * 每日任务服务类
 */
class DailyTask
{
    /**
     * 获取每日任务列表
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function taskList($data)
    {
        $task_list = DailyTaskModel::where('status', 1)->order('sort')->select();
        foreach ($task_list as $key => $value) {
            //判断能完成次数    0-为无数次
            if ($value['times'] == 0) {
                $task_list[$key]['is_pass']    = 0;
                $user_task_info                = DailyTaskRecordModel::where('user_id', $data['user_id'])->where('task_id', $value['id'])->where('dday', date('Ymd'))->find();
                $task_list[$key]['user_times'] = isset($user_task_info['times']) ? $user_task_info['times'] : 0;
                $task_list[$key]['get_gold']   = isset($user_task_info['gold']) ? $user_task_info['gold'] : 0;
            } else {
                //获取用户当前任务今日已完成次数
                $user_task_info                = DailyTaskRecordModel::where('user_id', $data['user_id'])->where('task_id', $value['id'])->where('dday', date('Ymd'))->find();
                $user_times                    = isset($user_task_info['times']) ? $user_task_info['times'] : 0;
                $task_list[$key]['user_times'] = $user_times;
                $task_list[$key]['get_gold']   = isset($user_task_info['gold']) ? $user_task_info['gold'] : 0;
                if ($user_times >= $value['times']) {
                    $task_list[$key]['is_pass'] = 1;
                } else {
                    $task_list[$key]['is_pass'] = 0;
                }
            }
        }
        return $task_list;
    }

    /**
     * 记录任务完成情况
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function taskFinishRecord($data)
    {
        $task_id_arr = explode(",", $data['task_id']);
        //邀请任务id
        $invite_id = $task_id_arr[1];
        //分享任务id
        $share_id = $task_id_arr[2];
        //判断是否为新用户
        if ($data['is_new'] == 1) {
            //邀请任务信息
            $invite_info = DailyTaskModel::where('id', $invite_id)->where('status', 1)->find();
            if ($invite_info) {
                //判断是否已邀请过
                $pid_invite_info = DailyTaskInviteModel::where('pid', $data['pid'])->where('user_id', $data['user_id'])->find();
                if (!$pid_invite_info) {
                    // 开启事务
                    Db::startTrans();
                    try {
                        //保存邀请记录
                        $daily_task_invite          = new DailyTaskInviteModel();
                        $daily_task_invite->pid     = $data['pid'];
                        $daily_task_invite->user_id = $data['user_id'];
                        $daily_task_invite->dday    = date('Ymd');
                        $daily_task_invite->save();
                        Db::commit();

                        //添加每日任务邀请记录
                        $daily_task_record = DailyTaskRecordModel::where('user_id', $data['pid'])->where('task_id', $invite_id)->where('dday', date('Ymd'))->lock(true)->find();
                        if ($daily_task_record) {
                            $daily_task_record->times += 1;
                            //判断单次结算还是一起结算
                            if ($invite_info['type'] == 1) {
                                //单次结算
                                if ($invite_info['times'] > 0) {
                                    if ($daily_task_record->times <= $invite_info['times']) {
                                        $daily_task_record->gold = ['inc', $invite_info['gold']];
                                    }
                                } else {
                                    $daily_task_record->gold = ['inc', $invite_info['gold']];
                                }
                            }
                            if ($invite_info['type'] == 0) {
                                //一起结算
                                if ($daily_task_record->times == $invite_info['times']) {
                                    $daily_task_record->gold = ['inc', $invite_info['gold']];
                                }
                            }
                            $daily_task_record->save();
                            Db::commit();
                        } else {
                            $daily_task_record            = new DailyTaskRecordModel();
                            $daily_task_record->task_id   = $invite_id;
                            $daily_task_record->user_id   = $data['pid'];
                            $daily_task_record->times     = 1;
                            $daily_task_record->all_times = $invite_info['times'];
                            //判断单次结算还是一起结算
                            if ($invite_info['type'] == 1) {
                                //单次结算
                                if ($invite_info['times'] > 0) {
                                    if ($daily_task_record->times <= $invite_info['times']) {
                                        $daily_task_record->gold = $invite_info['gold'];
                                    }
                                } else {
                                    $daily_task_record->gold = $invite_info['gold'];
                                }
                            }
                            if ($invite_info['type'] == 0) {
                                //一起结算
                                if ($daily_task_record->times == $invite_info['times']) {
                                    $daily_task_record->gold = $invite_info['gold'];
                                }
                            }
                            $daily_task_record->type = $invite_info['type'];
                            $daily_task_record->dday = date('Ymd');
                            $daily_task_record->save();
                            Db::commit();
                        }

                        //给用户加金币
                        $user_record = UserRecordModel::where('user_id', $data['pid'])->lock(true)->find();
                        if ($user_record) {
                            //判断单次结算还是一起结算
                            if ($invite_info['type'] == 1) {
                                //单次结算
                                if ($invite_info['times'] > 0) {
                                    if ($daily_task_record->times <= $invite_info['times']) {
                                        $user_record->gold = ['inc', $invite_info['gold']];
                                    }
                                } else {
                                    $user_record->gold = ['inc', $invite_info['gold']];
                                }
                            }
                            if ($invite_info['type'] == 0) {
                                //一起结算
                                if ($daily_task_record->times == $invite_info['times']) {
                                    $user_record->gold = ['inc', $invite_info['gold']];
                                }
                            }
                            $user_record->save();
                            Db::commit();
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                        lg($e);
                    }
                }
            } else {
                trace("不存在邀请任务id为{$invite_id}的任务信息", 'error');
            }
        }
        //是否有分享
        if (($data['iv'] != '') && ($data['encryptedData'] != '')) {
            //分享任务信息
            $share_info = DailyTaskModel::where('id', $share_id)->where('status', 1)->find();
            if ($share_info) {
                //解码数据
                $decrypt_data = $this->decrypt($data);
                if ($decrypt_data['code'] == 200) {
                    //分享记录信息
                    $daily_task_qun = DailyTaskQunModel::where('user_id', $data['pid'])->where('opengid', $decrypt_data['opengid'])->where('dday', date('Ymd'))->find();
                    // 开启事务
                    Db::startTrans();
                    try {
                        //判断今日是否分享过
                        if (!$daily_task_qun) {
                            //保存分享记录
                            $daily_task_qun          = new DailyTaskQunModel();
                            $daily_task_qun->user_id = $data['pid'];
                            $daily_task_qun->opengid = $decrypt_data['opengid'];
                            $daily_task_qun->dday    = date('Ymd');
                            $daily_task_qun->save();
                            Db::commit();

                            //添加每日任务邀请记录
                            $daily_task_record = DailyTaskRecordModel::where('user_id', $data['pid'])->where('task_id', $share_id)->where('dday', date('Ymd'))->lock(true)->find();
                            if ($daily_task_record) {
                                $daily_task_record->times += 1;
                                //判断单次结算还是一起结算
                                if ($share_info['type'] == 1) {
                                    //单次结算
                                    if ($share_info['times'] > 0) {
                                        if ($daily_task_record->times <= $share_info['times']) {
                                            $daily_task_record->gold = ['inc', $share_info['gold']];
                                        }
                                    } else {
                                        $daily_task_record->gold = ['inc', $share_info['gold']];
                                    }
                                }
                                if ($share_info['type'] == 0) {
                                    //一起结算
                                    if ($daily_task_record->times == $share_info['times']) {
                                        $daily_task_record->gold = ['inc', $share_info['gold']];
                                    }
                                }
                                $daily_task_record->save();
                                Db::commit();
                            } else {
                                $daily_task_record            = new DailyTaskRecordModel();
                                $daily_task_record->task_id   = $share_id;
                                $daily_task_record->user_id   = $data['pid'];
                                $daily_task_record->times     = 1;
                                $daily_task_record->all_times = $share_info['times'];
                                //判断单次结算还是一起结算
                                if ($share_info['type'] == 1) {
                                    //单次结算
                                    if ($share_info['times'] > 0) {
                                        if ($daily_task_record->times <= $share_info['times']) {
                                            $daily_task_record->gold = $share_info['gold'];
                                        }
                                    } else {
                                        $daily_task_record->gold = $share_info['gold'];
                                    }
                                }
                                if ($share_info['type'] == 0) {
                                    //一起结算
                                    if ($daily_task_record->times == $share_info['times']) {
                                        $daily_task_record->gold = $share_info['gold'];
                                    }
                                }
                                $daily_task_record->type = $share_info['type'];
                                $daily_task_record->dday = date('Ymd');
                                $daily_task_record->save();
                                Db::commit();
                            }

                            //给用户加金币
                            $user_record = UserRecordModel::where('user_id', $data['pid'])->lock(true)->find();
                            if ($user_record) {
                                //判断单次结算还是一起结算
                                if ($share_info['type'] == 1) {
                                    //单次结算
                                    if ($share_info['times'] > 0) {
                                        if ($daily_task_record->times <= $share_info['times']) {
                                            $user_record->gold = ['inc', $share_info['gold']];
                                        }
                                    } else {
                                        $user_record->gold = ['inc', $share_info['gold']];
                                    }
                                }
                                if ($share_info['type'] == 0) {
                                    //一起结算
                                    if ($daily_task_record->times == $share_info['times']) {
                                        $user_record->gold = ['inc', $share_info['gold']];
                                    }
                                }
                                $user_record->save();
                                Db::commit();
                            }
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                        lg($e);
                    }
                } else {
                    trace($data['pid'] . '-' . $decrypt_data['error'], 'error');
                }
            } else {
                trace("不存在分享任务id为{$share_id}的任务信息", 'error');
            }
        }
        return [
            'status' => 1,
            'msg'    => 'ok',
        ];
    }

    /**
     * 解码数据
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    private function decrypt($params)
    {
        $rs = ['code' => 400, 'error' => '发生异常', 'data' => ''];
        try {
            $iv            = $params['iv'];
            $encryptedData = $params['encryptedData'];
            if (strlen($iv) != 24) {
                $rs['error'] = 'iv参数有误';
                return $rs;
            }
            if (empty($encryptedData)) {
                $rs['error'] = '加密数据不存在';
                return $rs;
            }
            $session_key = UserModel::where('id', $params['pid'])->value('session_key');
            $aesKey      = base64_decode($session_key);
            $aesIV       = base64_decode($iv);
            $aesCipher   = base64_decode($encryptedData);
            $result      = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
            $dataObj     = json_decode($result);
            if ($dataObj == null) {
                $rs['error'] = 'session_key不存在或过期';
                return $rs;
            }
            $appid = $dataObj->watermark->appid;
            if ($appid != Config::get('wx_appid')) {
                $rs['error'] = 'appid有误';
                return $rs;
            }
            $opengid = $dataObj->openGId;
            $rs      = ['code' => 200, 'error' => 'success', 'opengid' => $opengid];
            return $rs;
        } catch (Exception $e) {
            $rs['error'] = '系统异常';
            return $rs;
        }

    }
}
