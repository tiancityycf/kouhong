<?php
//配置文件
return [
	// 微信设置
    'wx_appid' => 'wx0c09c35773b20ea2',
    'wx_secret' => '36579b86d95d0bfb289e18b770bde985',
    'wx_login_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    // 加密设置
    'app_secret' => 'SVnLVP43EG',
    'word_secret' => '',
    'str_secret' => '',

    // 提现设置
    'withdraw_url' => 'http://wxpay.wudee.cc/api/v1/withdraw_record/create_order', // 获取交易号URL
    'withdraw_secret' => 'SVnLVP43EG', // 提现加密密钥

    // 其他设置
    'default_return_type' => 'json', // 默认返回类型
    'exception_handle' => '\\app\\api\\exception\\ExceptionHandle', //自定义异常捕获类


    // +----------------------------------------------------------------------
    // | 其他设置
    // +----------------------------------------------------------------------
    // 缓存中应用的名称, pingyin("汉字填填看")
    'cache_app_name' => 'hzxbs',
    // 缓存中应用的唯一标识, substr(md5("汉字天天看"), 0, 5)
    'cache_app_uniq' => '829fc',
    // 配置的缓存时间(秒)
    'conf_cache_time' => 60,


    // +----------------------------------------------------------------------
    // | 网宿云配置
    // +----------------------------------------------------------------------
    'img_url_config' => 'https://txcdn.ylll111.xyz/', //图片地址配置
    'bucketName' => 'ylll111xyz', //空间名字
    'folder' => 'hzxbs/', //图片存储文件夹名字

    'config_key' => 'hzxbs:829fc:conf',
    'readme_key' => 'hzxbs:829fc:readme',

    'prize_key' => 'hzxbs:829fc:prizelist',

    'honor_key' => 'hzxbs:829fc:honorlist',
    'will_key' => 'hzxbs:829fc:willlist',
    'wealth_key' => 'hzxbs:829fc:wealthlist',
    'success_key' => 'hzxbs:829fc:successlist',

    'heimingdan_kaiguan' => 1,
];
