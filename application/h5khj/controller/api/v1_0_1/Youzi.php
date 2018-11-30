<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\h5khj\controller\api\v1_0_1;

use app\h5khj\model\Order as OrderModel;
use app\h5khj\model\RechargeAmount as RechargeAmountModel;
use app\h5khj\model\UserRecord as UserRecordModel;
use app\h5khj\model\UserRelationList as UserRelationListModel;
use app\h5khj\model\UserRelationRecord as UserRelationRecordModel;
use controller\BasicController;
use think\Db;
use think\facade\Request;

/**
 * 柚子支付控制类
 * @author 625575737@qq.com
 */
class Youzi extends BasicController
{
    public function prepay()
    {
        require_params('user_id', 'recharege_id');
        $params         = Request::param();
        $recharege_info = RechargeAmountModel::where('id', $params['recharege_id'])->where('status', 1)->find();
        if (!$recharege_info) {
            trace("{$params['recharege_id']}-" . json_encode($recharege_info), 'error');
            return [
                'status' => 0,
                'msg'    => '无效的充值额度',
            ];
        }
        // 开启事务
        Db::startTrans();
        try {
            //充值下单
            $order               = new OrderModel();
            $order->user_id      = $params['user_id'];
            $order->trade_no     = $this->getTradeNo('KH');
            $order->recharege_id = $params['recharege_id'];
            if (isset($params['good_id']) && $params['good_id'] != '') {
                $order->good_id = $params['good_id'];
            }
            $order->pay_money = $recharege_info['money'];
            $order->type      = 0;
            $order->dday      = date('Ymd');
            $order->addtime   = date('Y-m-d H:i:s');
            $order->save();
            Db::commit();

            $data = Request::param();

            $data['user_id']      = $order->user_id; //用户ID
            $data['goods_id']     = $order->recharege_id; //商品ID
            $data['goods_name']   = $recharege_info['title']; //商品名称
            $data['recharege_id'] = $order->trade_no; //生成的支付单号
            $data['amt']          = $order->pay_money * 100; //充值的金额 单位分

            $data['appid']     = config('h5_appid');
            $url               = 'http://finance.youzikj.com/finance/finance/recharge';
            $json              = [];
            $json['imei']      = "";
            $json['account']   = $data['user_id'];
            $json['appId']     = $data['appid'];
            $json['channelId'] = "00000000";
            $json['payType']   = 1;
            $json['goodsId']   = $data['goods_id'];
            $json['goodsName'] = $data['goods_name'];
            $json['payAmt']    = $data['amt'];
            $json['mac']       = "";
            $json['orderId']   = $data['recharege_id'];
            $json['isPhone']   = 1;
            $json['notifyUrl'] = config('h5_notify');
            $json['returnUrl'] = "";
            $json['remark']    = "";
            $json              = json_encode($json);

            $data = $this->dorequest($url, $json);

            //返回结果
            if (!empty($data['url'])) {
                header('Location: ' . $data['url']);exit();
            } else {
                return false;
            }

        } catch (\Exception $e) {
            lg($e);
            Db::rollback();
        }
    }

    /**
     * 生成订单号
     * @param  [type] $prefix 订单前缀
     * @return [type]         [description]
     */
    public function getTradeNo($prefix)
    {
        return $prefix . time() . substr(microtime(), 2, 5) . rand(0, 9);
    }

    /**
     * 支付回调
     * @return string
     * 回调结果为获取JSON串
     *{
     *"amount": "1",//支付金额,单位为分
     *"orderId": "201801aacca36104816",//订单ID，CP方提交订单的时候的订单ID
     *"extra": "abced0",//额外的参数，用goodsId来传服务器和用户ID，回调的时候用extra参数来获取
     *"status": "1"//状态，0是失败，1是成功
     *}
     */
    public function notify()
    {
        $postdata = file_get_contents('php://input');
        trace($postdata, 'error');
        $data = json_decode($postdata, true);

        if ($data['status'] == 1) {
            //没有签名验证，只能去查询订单状态验证了
            if ($this->valid($data['orderId'])) {
                // 开启事务
                Db::startTrans();
                try {
                    //支付成功 这里 写自己的业务逻辑
                    $order = OrderModel::where('trade_no', $data['orderId'])->lock(true)->find();
                    if ($order) {
                        if ($order['status'] == 1) {
                            $result            = [];
                            $result['success'] = 'true'; //必须用字符串 尴尬
                            echo json_encode($result);exit(); //不要删除 exit
                        } else {
                            if (($order['pay_money'] * 100) == $data['amount']) {
                                //更改订单状态
                                $order->status   = 1;
                                $order->pday     = date('Ymd');
                                $order->pay_time = date('Y-m-d H:i:s');
                                $order->save();
                                //给用户增加金额
                                $user_record = UserRecordModel::where('openid', $param['openid'])->find();
                                if ($user_record) {
                                    $user_record->money       = ['inc', $order['pay_money']];
                                    $user_record->total_money = ['inc', $order['pay_money']];
                                    $user_record->save();

                                    //判断是否存在上级
                                    $relation_info = UserRelationListModel::where('user_id', $user_record['user_id'])->find();
                                    if ($relation_info) {
                                        $config_data = $this->configData;
                                        $br_money    = $order['pay_money'] * $config_data['one_distribution_br'];
                                        //添加分销记录
                                        $user_relation_record            = new UserRelationRecordModel();
                                        $user_relation_record->user_id   = $user_record['user_id'];
                                        $user_relation_record->pid       = $relation_info['pid'];
                                        $user_relation_record->br        = $config_data['one_distribution_br'];
                                        $user_relation_record->pay_money = $order['pay_money'];
                                        $user_relation_record->dis_money = $br_money;
                                        $user_relation_record->save();
                                        $relation_pid_record = UserRecordModel::where('user_id', $relation_info['pid'])->find();
                                        if ($relation_pid_record) {
                                            //给上级增加分销金额
                                            $relation_pid_record->dis_money = ['inc', $br_money];
                                            $relation_pid_record->save();
                                        }
                                    }
                                }
                                Db::commit();

                                $result            = [];
                                $result['success'] = 'true'; //必须用字符串 尴尬
                                echo json_encode($result);exit(); //不要删除 exit
                            } else {
                                //更改订单状态
                                $order->status   = 2;
                                $order->pday     = date('Ymd');
                                $order->pay_time = date('Y-m-d H:i:s');
                                $order->save();
                                Db::commit();

                                $result            = [];
                                $result['success'] = 'false'; //必须用字符串 尴尬
                                echo json_encode($result);exit(); //不要删除 exit
                            }
                        }
                    } else {
                        $result            = [];
                        $result['success'] = 'false'; //必须用字符串 尴尬
                        echo json_encode($result);exit();
                        Db::rollback();
                    }
                } catch (\Exception $e) {
                    lg($e);
                    Db::rollback();
                }
            } else {
                $result            = [];
                $result['success'] = 'false'; //必须用字符串 尴尬
                echo json_encode($result);exit();
            }
        } else {
            trace($postdata, 'critical');
            $result            = [];
            $result['success'] = 'false'; //必须用字符串 尴尬
            echo json_encode($result);exit();
        }
    }

    private function valid($order_id)
    {
        //没有签名验证，只能去查询订单状态验证了
        $url             = 'http://finance.youzikj.com/finance/finance/orderStatus';
        $json['orderId'] = $order_id;
        $json['appid']   = config('h5_appid');
        $json            = json_encode($json);
        $data            = $this->dorequest($url, $json);
        trace(json_encode($data), 'error');
        if ($data['state'] == 2) {
            return true;
        } else {
            trace(json_encode($data), 'critical');
            return false;
        }
    }
    private function dorequest($url, $json)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        //运行curl
        $data = curl_exec($ch);
        $data = json_decode($data, true);
        curl_close($ch);
        return $data;
    }
}
