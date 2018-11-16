<?php

namespace app\khj\service\v1_0_1;

use app\khj\model\Goods as GoodsModel;
use app\khj\model\Order as OrderModel;
use think\Db;
use think\facade\Config;

/**
 * 微信支付服务类
 */
class WxPay
{
    protected $configData;

    public function __construct($configData = [])
    {
        $this->configData = $configData;
    }

    /**
     * 统一下单
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function unifiedorder($data)
    {
        // 开启事务
        Db::startTrans();
        try {
            //商户下单
            if ($data['type'] == 0) {
                //充值下单
                // $order           = new OrderModel();
                // $order->user_id  = $data['user_id'];
                // $order->trade_no = $this->getTradeNo('KH');
            } elseif ($data['type'] == 1) {
                //商品信息
                $good_info = GoodsModel::get($data['good_id']);
                //购买下单
                $order             = new OrderModel();
                $order->user_id    = $data['user_id'];
                $order->trade_no   = $this->getTradeNo('KH');
                $order->good_id    = $data['good_id'];
                $order->good_name  = $good_info['title'];
                $order->good_value = $good_info['price'];
                $order->num        = $data['num'];
                $order->pay_value  = $good_info['price'] * $data['num'];
                $order->type       = 1;
                $order->dday       = date('Ymd');
                $order->add_time   = date('Y-m-d H:i:s');
                $order->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            lg($e);
            Db::rollback();
        }
        //调用微信下单接口下单
        //小程序id
        $appid = Config::get('wx_appid');
        //商户id
        $mch_id = Config::get('wx_mch_id');
        //随机字符串
        $nonce_str = md5(time() . rand(10000, 99999));
        //签名类型
        $sign_type = 'MD5';
        //商品描述
        $body = $order->good_name;
        //生成订单号
        $out_trade_no = $order->trade_no;
        //订单总金额
        $total_fee = $order->pay_value * 100;
        //终端IP
        $spbill_create_ip = $_SERVER['REMOTE_ADDR'];
        //回调地址
        $notify_url = Config::get('wx_notify_url');
        //交易类型
        $trade_type = 'JSAPI';
        $param      = [
            'appid'            => $appid,
            'mch_id'           => $mch_id,
            'nonce_str'        => $nonce_str,
            'sign_type'        => $sign_type,
            'body'             => $body,
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $total_fee,
            'spbill_create_ip' => $spbill_create_ip,
            'notify_url'       => $notify_url,
            'trade_type'       => $trade_type,
        ];
        ksort($param);
        //商户平台设置的密钥key
        $wx_mch_key = Config::get('wx_mch_key');
        //签名
        $sign          = $this->getmd5sec($param, $wx_mch_key);
        $param['sign'] = $sign;
        $xml           = $this->data_to_xml($param);
        //微信统一下单接口
        $wx_pay_unifiedorder = Config::get('wx_pay_unifiedorder');
        $response            = $this->postXmlCurl($xml, $wx_pay_unifiedorder);
        if (!$response) {
            return false;
        }
        $result = $this->xml_to_data($response);
        if (!empty($result['result_code']) && !empty($result['err_code'])) {
            $result['err_msg'] = $this->error_code($result['err_code']);
        }
        return $result;
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
     * 获取签名信息
     * @param  [type] $param         接收参数
     * @param  [type] $wx_mch_key     商户平台设置的密钥key
     * @return [type]        [description]
     */
    public function getmd5sec($param, $wx_mch_key)
    {
        ksort($param);
        $tmp = "";
        foreach ($param as $k => $v) {
            if ($v) {
                $tmp .= "{$k}={$v}&";
            }
        }
        $tmp .= "key={$wx_mch_key}";
        return strtoupper(md5($tmp));
    }

    /**
     * 输出xml字符
     * @param $params 参数名称
     * return string 返回组装的xml
     **/
    public function data_to_xml($params)
    {
        if (!is_array($params) || count($params) <= 0) {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @throws WxPayException
     */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            //curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            //curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }

    /**
     * 将xml转为array
     * @param string $xml
     * return array
     */
    public function xml_to_data($xml)
    {
        if (!$xml) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /**
     * 错误代码
     * @param $code 服务器输出的错误代码
     * return string
     */
    public function error_code($code)
    {
        $errList = array(
            'NOAUTH'                => '商户未开通此接口权限',
            'NOTENOUGH'             => '用户帐号余额不足',
            'ORDERNOTEXIST'         => '订单号不存在',
            'ORDERPAID'             => '商户订单已支付，无需重复操作',
            'ORDERCLOSED'           => '当前订单已关闭，无法支付',
            'SYSTEMERROR'           => '系统错误!系统超时',
            'APPID_NOT_EXIST'       => '参数中缺少APPID',
            'MCHID_NOT_EXIST'       => '参数中缺少MCHID',
            'APPID_MCHID_NOT_MATCH' => 'appid和mch_id不匹配',
            'LACK_PARAMS'           => '缺少必要的请求参数',
            'OUT_TRADE_NO_USED'     => '同一笔交易不能多次提交',
            'SIGNERROR'             => '参数签名结果不正确',
            'XML_FORMAT_ERROR'      => 'XML格式错误',
            'REQUIRE_POST_METHOD'   => '未使用post传递参数 ',
            'POST_DATA_EMPTY'       => 'post数据不能为空',
            'NOT_UTF8'              => '未使用指定编码格式',
        );
        if (array_key_exists($code, $errList)) {
            return $errList[$code];
        }
    }

    /**
     * 下单回调
     * @return string
     */
    public function unifiedorderNotify($data)
    {
        $order = OrderModel::where('trade_no', $data['trade_no'])->find();
        if ($order) {
            // 开启事务
            Db::startTrans();
            try {
                if ($data['code'] == 200) {
                    $order->status   = 1; // 成功
                    $order->pay_time = date('Y-m-d H:i:s');
                } else {
                    $order->status   = 2; // 失败
                    $order->pay_time = date('Y-m-d H:i:s');
                }
                $order->save();
                Db::commit();
                return 'SUCCESS';
            } catch (\Exception $e) {
                lg($e);
                Db::rollback();
                return 'FAILUE';
            }
        }
        return 'FAILUE';
    }
}
