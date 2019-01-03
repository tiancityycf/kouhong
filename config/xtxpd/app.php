<?php
//配置文件
return [
    'ad_url'              => 'https://ad.ali-yun.wang',
    'config_key'          => 'xtxpd:config_info',
    //异常系统接入参数
    'system_title'        => '小甜心炮弹',
    'system_sign'         => 'khj.wqop2018.comxtxpd',
    'system_url'          => 'http://log.zxmn2018.com/logs/api/v1/exceptionlog/writeExceptionlog',

    // 微信设置
    'wx_appid'            => 'wx2758adfc899a5936',
    'wx_secret'           => '52a36504b4afdd537c10a826e9d81b40',
    'wx_login_url'        => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

];
