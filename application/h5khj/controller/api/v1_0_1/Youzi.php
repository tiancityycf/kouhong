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
 * 柚子支付控制类
 * @author 625575737@qq.com
 */
class Youzi extends BasicController
{
    public function prepay()
    {
//        require_params('user_id', 'type', 'recharege_id');
        $data = Request::param();

        $data['user_id'] = 11; //用户ID
        $data['goods_id'] = 11; //商品ID
        $data['goods_name'] = '5yuan';//商品名称
        $data['recharege_id'] = '11111'.rand(100,999);//生成的支付单号
        $data['amt'] = 1;//充值的金额 单位分

        $data['appid'] = config('h5_appid');
        $url = 'http://finance.youzikj.com/finance/finance/recharge';
        $json = [];
        $json['imei'] = "";
        $json['account'] = $data['user_id'];
        $json['appId'] = $data['appid'];
        $json['channelId'] = "00000000";
        $json['payType'] = 1;
        $json['goodsId'] = $data['goods_id'];
        $json['goodsName'] = $data['goods_name'];
        $json['payAmt'] = $data['amt'];
        $json['mac'] = "";
        $json['orderId'] = $data['recharege_id'];
        $json['isPhone'] = 1;
        $json['notifyUrl'] = config('h5_notify');
        $json['returnUrl'] = "";
        $json['remark'] = "";
        $json = json_encode($json);

        $data = $this->dorequest($url,$json);

        //返回结果
        if (!empty($data['url'])) {
            header('Location: '.$data['url']);exit();
        } else {
            return false;
        }
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
        trace($postdata,'error');
        $data = json_decode($postdata,true);

        if($data['status']==1){
            //没有签名验证，只能去查询订单状态验证了
            if($this->valid($data['orderId'])){
                //支付成功 这里 写自己的业务逻辑



                $result = [];
                $result['success'] = 'true'; //必须用字符串 尴尬
                echo json_encode($result);exit();//不要删除 exit
            }else{
                $result = [];
                $result['success'] = 'false';//必须用字符串 尴尬
                echo json_encode($result);exit();
            }
        }else{
            trace($postdata,'critical');
            $result = [];
            $result['success'] = 'false';//必须用字符串 尴尬
            echo json_encode($result);exit();
        }
    }

    private function valid($order_id)
    {
        //没有签名验证，只能去查询订单状态验证了
        $url = 'http://finance.youzikj.com/finance/finance/orderStatus';
        $json['orderId'] = $order_id;
        $json['appid'] = config('h5_appid');
        $json = json_encode($json);
        $data = $this->dorequest($url,$json);
        trace(json_encode($data),'error');
        if($data['state']==2){
            return true;
        }else{
            trace(json_encode($data),'critical');
            return false;
        }
    }
    private function dorequest($url,$json){
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
        curl_close($ch);
        return $data;
    }
}
