<?php
//配置文件
return [
	// 微信设置
    'wx_appid' => 'wxce252f115355600f',
    'wx_secret' => '9a99d4fb43862f628fcaa3291c1cd011',
    'wx_login_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    // 加密设置
    'app_secret' => '',
    'word_secret' => '',
    'str_secret' => '',

    // 其他设置
    'default_return_type' => 'json', // 默认返回类型
    'exception_handle' => '\\app\\api\\exception\\ExceptionHandle', //自定义异常捕获类
];
