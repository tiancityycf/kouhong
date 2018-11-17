<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\khj\controller\api\v1_0_1;

use app\khj\service\v1_0_1\WxPay as WxPayService;
use controller\BasicController;
use think\facade\Request;

/**
 * 微信支付控制类
 *
 * @author 625575737@qq.com
 */
class WxPay extends BasicController
{
    public function unifiedorder()
    {
        require_params('user_id', 'type', 'recharege_id');
        $data = Request::param();

        $wx_pay_service = new WxPayService($this->configData);
        $result         = $wx_pay_service->unifiedorder($data);

        return result(200, 'ok', $result);
    }

    /**
     * 下单回调
     * @return string
     */
    public function unifiedorderNotify()
    {
        $xml = file_get_contents('php://input');
        trace($xml,'error');
        $wx_pay_service = new WxPayService();
        $result = $wx_pay_service->unifiedorderNotify($xml);
        echo $result;
    }
}
