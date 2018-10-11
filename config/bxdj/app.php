<?php
//配置文件
return [
    // 微信设置
    'wx_appid' => 'wxbe7091bfd1ddf7e9',
    'wx_secret' => '1e9a6cfb36ba76972655e718718a200b',
    'wx_login_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    // 加密设置
    'app_secret' => 'sDyoKPS0X1',
    'word_secret' => '',
    'str_secret' => '',

    // 其他设置
    'default_return_type' => 'json', // 默认返回类型
    'exception_handle' => '\\app\\api\\exception\\ExceptionHandle', //自定义异常捕获类

    'config_key' => 'bxdj:conf',

    'goods_info'=>'goods_list',

    'dairy_info'=>'dairy_list',

    'message_info' => 'message_list',

    'rules_info' => 'rules_info',

    'questions_info' => 'questions_info',

    'activity_info' => 'bxdj:activity_list',

    //获取服务消息的access_token的url
    'get_access_url' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s',

    'send_template_url' => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=%s',

    'garbage_word_check_url' => 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=%s' 

];
