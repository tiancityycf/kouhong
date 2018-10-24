<?php
//配置文件
return [

    // 加密设置
    'app_secret'   => 'QKTWuxzM5EfkeQhi',
    'word_secret'  => '',
    'str_secret'   => '',

    // 微信设置
    'wx_appid'     => 'wxb22df5347915b0aa',
    'wx_secret'    => '2f2c49f504048edf1673dcc49bf21077',
    'wx_login_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    //'exception_handle' => '\\exception\\ExceptionHandle', //自定义异常捕获类

    'ad_url'       => 'https://ad.ali-yun.wang',
//    'ad_url'=>'http://gg.com:8083',

    'goods_info'   => 'qmxz:goods_info',
    'config_key'   => 'qmxz:config_info',

    //异常系统接入参数
    'system_title' => '全民选择',
    'system_sign'  => 'qmxz.comqmxz',
    'system_url'   => 'http://log.zxmn2018.com/logs/api/v1/exceptionlog/writeExceptionlog',

    // 提现设置
    'withdraw_url' => 'http://wxpay.wudee.cc/api/v1/withdraw_record/create_order', // 获取交易号URL

    //提现加密密钥
    'withdraw_secret' => 'cVCM47ZDqD',

];
