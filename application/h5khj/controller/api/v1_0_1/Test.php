<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\h5khj\controller\api\v1_0_1;

use controller\BasicController;
use think\facade\Request;

/**
 * 支付控制类
 *
 * @author 625575737@qq.com
 */
class Test extends BasicController
{
    public function prepay()
    {
//        require_params('user_id', 'type', 'recharege_id');
        $data = Request::param();

        $data['user_id'] = 11;
        $data['goods_id'] = 11;
        $data['goods_name'] = '5yuan';
        $data['recharege_id'] = '11111'.rand(100,999);
        $data['appid'] = '2018113001';
        $url = 'http://finance.youzikj.com/finance/finance/recharge';
        $json = [];
        $json['imei'] = "";
        $json['account'] = $data['user_id'];
        $json['appId'] = $data['appid'];
        $json['channelId'] = "00000000";
        $json['payType'] = 1;
        $json['goodsId'] = $data['goods_id'];
        $json['goodsName'] = $data['goods_name'];
        $json['payAmt'] = 1;
        $json['mac'] = "";
        $json['orderId'] = $data['recharege_id'];
        $json['isPhone'] = 1;
        $json['notifyUrl'] = "";
        $json['returnUrl'] = "";
        $json['remark'] = "";
        $json = json_encode($json);

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
        $data = json_decode($data,true);

        //返回结果
        if (!empty($data['url'])) {
            curl_close($ch);
            header('Location: '.$data['url']);exit();
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            trace($error, 'error');
            return false;
        }
    }

    /**
     * 下单回调
     * @return string
     */
    public function notify()
    {
        $xml = file_get_contents('php://input');
        $wx_pay_service = new WxPayService();
        $result = $wx_pay_service->unifiedorderNotify($xml);
        echo $result;
    }
}
