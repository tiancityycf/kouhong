<?php

namespace app\h5khj\service\v1_0_1;

use app\h5khj\model\RechargeAmount as RechargeAmountModel;

/**
 * 充值服务类
 */
class RechargeAmount
{
    /**
     * 获取充值额度列表
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function amount_list($data)
    {
        $list = RechargeAmountModel::where('status', 1)->order('sort')->select();
        return $list;
    }
}
