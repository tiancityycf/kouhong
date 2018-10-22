<?php
//配置文件
return [

    // 加密设置
    'app_secret'       => 'QKTWuxzM5EfkeQhi',
    'word_secret'      => '',
    'str_secret'       => '',

    // 微信设置
    'wx_appid'         => 'wxbe7091bfd1ddf7e9',
    'wx_secret'        => '1e9a6cfb36ba76972655e718718a200b',
    'wx_login_url'     => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    //'exception_handle' => '\\exception\\ExceptionHandle', //自定义异常捕获类

    'ad_url'           => 'https://ad.ali-yun.wang',
//    'ad_url'=>'http://gg.com:8083',

    'topic_key'        => 'qmxz:829fc:topic',
    'topic_word_key'   => 'qmxz:829fc:topicword',
    'select_topic_key' => 'qmxz:829fc:selecttopic',

];
