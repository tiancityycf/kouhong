<?php
//配置文件
return [
	// 微信设置
    'wx_appid' => 'wx0fb7112b00e86674',
    'wx_secret' => '1b0c23b8fe353a2c7fb034737a9b4c84',
    'wx_login_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    // 加密设置
    'app_secret' => 'W4jpqClV2y',
    'word_secret' => '',
    'str_secret' => '',

    // 提现设置
    'withdraw_url' => 'http://wxpay.wudee.cc/api/v1/withdraw_record/create_order', // 获取交易号URL
    'withdraw_secret' => 'W4jpqClV2y', // 提现加密密钥

    // 其他设置
    'default_return_type' => 'json', // 默认返回类型
    'exception_handle' => '\\app\\api\\exception\\ExceptionHandle', //自定义异常捕获类


    // +----------------------------------------------------------------------
    // | 其他设置
    // +----------------------------------------------------------------------
    // 缓存中应用的名称, pingyin("汉字填填看")
    'cache_app_name' => 'lbsz',
    // 缓存中应用的唯一标识, substr(md5("汉字天天看"), 0, 5)
    'cache_app_uniq' => '829fc',
    // 配置的缓存时间(秒)
    'conf_cache_time' => 60,


    // +----------------------------------------------------------------------
    // | 网宿云配置
    // +----------------------------------------------------------------------
    'img_url_config' => 'https://txcdn.ylll111.xyz/', //图片地址配置
    'bucketName' => 'ylll111xyz', //空间名字
    'folder' => 'lbsz/', //图片存储文件夹名字

    'config_key' => 'lbsz:829fc:conf',
    'readme_key' => 'lbsz:829fc:readme',

    'prize_key' => 'lbsz:829fc:prizelist',

    'honor_key' => 'lbsz:829fc:honorlist',
    'will_key' => 'lbsz:829fc:willlist',
    'wealth_key' => 'lbsz:829fc:wealthlist',
    'success_key' => 'lbsz:829fc:successlist',

    'heimingdan_kaiguan' => 1,
];
