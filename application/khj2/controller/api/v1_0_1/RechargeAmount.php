<?php

namespace app\khj2\controller\api\v1_0_1;

use app\khj2\service\v1_0_1\RechargeAmount as RechargeAmountService;
use controller\BasicController;
use think\facade\Request;

/**
 * 充值控制器类
 */
class RechargeAmount extends BasicController
{
    public function amount_list()
    {
        require_params('user_id');
        $data = Request::param();

        $rechage_amount = new RechargeAmountService($this->configData);
        //充值额度列表
        $result = $rechage_amount->amount_list($data);

        return result(200, 'ok', $result);
    }
}
