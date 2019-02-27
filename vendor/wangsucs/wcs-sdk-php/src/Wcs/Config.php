<?php

namespace  Wcs;


final class Config
{
    //version
    const WCS_SDK_VER = "2.0.4";


    //url设置
    const WCS_PUT_URL	= 'muzi.up34.v1.wcsapi.com'; //WCS put 上传路径
    const WCS_GET_URL	= 'http://GET_URL';    //WCS get 上传路径
    const WCS_MGR_URL	= 'muzi.mgr34.v1.wcsapi.com';    //WCS MGR 路径

    //access key and secret key
    const WCS_ACCESS_KEY	= '436993c4466249cf7aff913fe31a31f78e477b2c';
    const WCS_SECRET_KEY	= '89677a149b4c4c79878f1b3f463d72c9254c0a11';

    //token的deadline,默认是1小时,也就是3600s
    const  WCS_TOKEN_DEADLINE = 3600;

    //上传文件设置
    const WCS_OVERWRITE = 1; //默认文件不覆盖
    //超时时间
    const WCS_TIMEOUT = 30;
    const WCS_CONNECTTIMEOUT = 30;

    //虚拟内存目录
    const WCS_RAM_URL = '/mnt/ramdisk/';

    //分片上传参数设置
    const WCS_BLOCK_SIZE = 4194304; //4 * 1024 * 1024 默认块大小4M
    const WCS_CHUNK_SIZE = 524288; //  4 * 1024 * 1024 默认片大小4M
    //const WCS_CHUNK_SIZE = 4194304; //  4 * 1024 * 1024 默认片大小4M
    const WCS_RECORD_URL = '/root/s3-tests/upload/'; //默认当前文件目录
    const WCS_COUNT_FOR_RETRY = 3;  //超时重试次数

    //并发请求数目
    const WCS_CONCURRENCY = 5;



}

