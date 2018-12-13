<?php
//配置文件
return [
    // 加密设置
    'app_secret'          => 'QKTWuxzM5EfkeQhi',
    'word_secret'         => '',
    'str_secret'          => '',

    'applist'             => [
        'wx6acc78416f0a6aca' => '0bf1f0a099df61011773850a49e45310', //心动红唇
        'wx7fdd85975ac19c6b' => '06f1bbc9f62abe1368de3b4e0c92e376', //唇色声香
        'wxb8aaef731e5a3c47' => '97561711997a17b144c6827e170d5f0b', //红唇秀
    ],
    // 微信设置
    'wx_appid'            => 'wxe1e2993b454a338e',
    'wx_secret'           => 'fb9269f412b8d289c50126ec45eca6f0',
    //商户号
    'wx_mch_id'           => '1519068881',
    //商户平台设置的密钥key
    'wx_mch_key'          => 'Bbcv5nqrnwtU0dReNd8WkZ8Yvy5DBH8G',
    //微信授权url
    'wx_authorize_url'    => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=%s#wechat_redirect",
    'wx_login_url'        => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",
    //微信access_token获取接口
    'get_access_url'      => 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code',
    //微信获取用户信息接口
    'wx_user_info_url'    => "https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN",

    'exception_handle'    => '\\exception\\ExceptionHandle', //自定义异常捕获类

    'ad_url'              => 'https://ad.ali-yun.wang',
//    'ad_url'=>'http://gg.com:8083',

    'config_key'          => 'kouhongji:config_info',

    //异常系统接入参数
    'system_title'        => 'H5口红机',
    'system_sign'         => 'khj.wqop2018.comh5khj',
    'system_url'          => 'http://log.zxmn2018.com/logs/api/v1/exceptionlog/writeExceptionlog',

    // 提现设置
    'withdraw_url'        => 'http://wxpay.wudee.cc/api/v1/withdraw_record/create_order', // 获取交易号URL

    //提现加密密钥
    'withdraw_secret'     => 'cVCM47ZDqD',

    //发送模板消息url
    'send_template_url'   => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=%s',

    //微信统一下单接口
    'wx_pay_unifiedorder' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',

    //微信下单回调
    'wx_notify_url'       => 'https://khj.wqop2018.com/h5khj/api/v1_0_1/wx_pay/unifiedorderNotify.html',

    //抓取商品信息接口地址
    'capture_data_url'    => 'https://jingubang.taoanli.cn/app/index.php?i=717&t=0&v=1.2.2&from=wxapp&c=entry&a=wxapp&do=goods&&m=hc_doudou&sign=ab9a95145480b576c3277332661f35de',

    //登录domain
    'login_domain'=>'https://khj.wqop2018.com/kouhongji/login/index.html',
];
