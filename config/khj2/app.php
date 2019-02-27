<?php
//配置文件
return [
    // 加密设置
    'app_secret'          => 'QKTWuxzM5EfkeQhi',
    'word_secret'         => '',
    'str_secret'          => '',

    // 微信设置
    'wx_appid'            => 'wx36d249695ef0c93c',
    'wx_secret'           => 'daa45d4f334828c2a37dfc11d47564df',

    'exception_handle' => '\\exception\\ExceptionHandle', //自定义异常捕获类

    'ad_url'              => 'https://ad.ali-yun.wang',
//    'ad_url'=>'http://gg.com:8083',


    'config_key'         => 'khj2:config_info',

    //异常系统接入参数
//    'system_title'        => '红唇秀第二版',
//    'system_sign'         => 'khj2.wqop2018.comkhj2',
//    'system_url'          => 'http://log.zxmn2018.com/logs/api/v1/exceptionlog/writeExceptionlog',


    //微信access_token获取接口
    'get_access_url'      => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s',

    //发送模板消息url
    'send_template_url'   => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=%s',
];
