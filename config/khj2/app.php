<?php
//配置文件
return [
    // 加密设置
    'app_secret'          => 'QKTWuxzM5EfkeQhi',

    // 微信设置
    'game_id'            => 1,
    'game_name'            => '口红机',
    'wx_appid'            => 'wx36d249695ef0c93c',
    'wx_secret'           => 'daa45d4f334828c2a37dfc11d47564df',
    'wx_login_url'        => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    'exception_handle' => '\\exception\\ExceptionHandle', //自定义异常捕获类

    'config_key'         => 'khj2:config_info',

    //微信access_token获取接口
    'get_access_url'      => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s',

    //发送模板消息url
    'send_template_url'   => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=%s',
];
