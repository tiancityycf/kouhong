<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

use service\DataService;
use service\NodeService;
use think\Db;

/**
 * 打印输出数据到文件
 * @param mixed $data 输出的数据
 * @param bool $force 强制替换
 * @param string|null $file
 */
function p($data, $force = false, $file = null)
{
    is_null($file) && $file = env('runtime_path') . date('Ymd') . '.txt';
    $str                    = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
    $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
}

/**
 * RBAC节点权限验证
 * @param string $node
 * @return bool
 */
function auth($node)
{
    return NodeService::checkAuthNode($node);
}

/**
 * 设备或配置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是null为获取值，否则为更新
 * @return string|bool
 * @throws \think\Exception
 * @throws \think\exception\PDOException
 */
function sysconf($name, $value = null)
{
    static $config = [];
    if ($value !== null) {
        list($config, $data) = [[], ['name' => $name, 'value' => $value]];
        return DataService::save('SystemConfig', $data, 'name');
    }
    if (empty($config)) {
        $config = Db::name('SystemConfig')->column('name,value');
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * 日期格式标准输出
 * @param string $datetime 输入日期
 * @param string $format 输出格式
 * @return false|string
 */
function format_datetime($datetime, $format = 'Y年m月d日 H:i:s')
{
    return date($format, strtotime($datetime));
}

/**
 * UTF8字符串加密
 * @param string $string
 * @return string
 */
function encode($string)
{
    list($chars, $length) = ['', strlen($string = iconv('utf-8', 'gbk', $string))];
    for ($i = 0; $i < $length; $i++) {
        $chars .= str_pad(base_convert(ord($string[$i]), 10, 36), 2, 0, 0);
    }
    return $chars;
}

/**
 * UTF8字符串解密
 * @param string $string
 * @return string
 */
function decode($string)
{
    $chars = '';
    foreach (str_split($string, 2) as $char) {
        $chars .= chr(intval(base_convert($char, 36, 10)));
    }
    return iconv('gbk', 'utf-8', $chars);
}

/**
 * 下载远程文件到本地
 * @param string $url 远程图片地址
 * @return string
 */
function local_image($url)
{
    return \service\FileService::download($url)['url'];
}

/**
 * 统一校验参数
 * @param  string ...$params
 * @return string
 */
function require_params(...$params)
{
    foreach ($params as $param) {
        if (!Request::has($param)) {
            echo json_encode(['code' => 500, 'msg' => '缺少必要的参数'], JSON_UNESCAPED_UNICODE);exit();
        }
    }
}

/**
 * 统一返回
 * @param  integer $code 状态值
 * @param  string $msg 信息
 * @param  array $data 数据
 * @return json
 */
function result($code = 200, $msg = 'ok', $data = [])
{
    return json([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data,
    ]);
}

/**
 * 汉字加密
 * @return string
 */
function hanzi_encode($str)
{
    return substr(strtoupper(md5($str)), 10, 10);
}

/**
 * 统一加密函数
 * @param  $str 待加密字符串
 * @return string
 */
function str_encode($str)
{
    return base64_encode($str);
}

/**
 * 统一解密函数
 * @param  $str 加密字符串
 * @return string
 */
function str_decode($str)
{
    return base64_decode($str);
}

function https_get($url)
{
    $ch      = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $file_contents = curl_exec($ch);
    curl_close($ch);

    return $file_contents;
}

/**
 * 发送POST请求
 * @param  string $url  请求路径
 * @param  array $data 请求参数
 * @return [type]       [description]
 */
function sendCmd($url, $data)
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 1); // 设置超时限制防止死循
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno' . curl_error($curl);
    }
    curl_close($curl); // 关键CURL会话
    return $tmpInfo; // 返回数据
}
/**
 * 保存系统异常日志到异常日志系统
 * @param  array $sendData 异常参数
 * @return [type]           [description]
 */
function slg($type, $msg)
{
    $err_arr = ['emergency', 'alert', 'critical', 'error'];
    if (in_array($type, $err_arr)) {
        $send_url = config('system_url');
        $sendData = [
            'type'        => 2,
            'title'       => config('system_title'),
            'system_sign' => config('system_sign'),
            'log_type'    => $type,
            'msg'         => $msg,
        ];
        sendCmd($send_url, $sendData);
    }
}

/**
 * 保存接口异常信息到异常日志系统
 * @param  object $e      try...catch...返回对象
 * @param  string $remark 备注信息
 * @return [type]         [description]
 */
function lg($e, $remark = '')
{
    $send_url = config('system_url');
    $sendData = [
        'type'        => 1,
        'title'       => config('system_title'),
        'system_sign' => config('system_sign'),
        'code'        => $e->getCode(),
        'error_msg'   => $e->getMessage(),
        'path'        => $e->getFile() . ':' . $e->getLine(),
        'remark'      => $remark,
    ];
    sendCmd($send_url, $sendData);
}

/**
 * 生成原始的二维码(生成图片文件)
 * @param  string $url 链接
 * @return [type]      [description]
 */
function createQr($url = '')
{
    require_once '../vendor/phpqrcode/phpqrcode.php';
    $value                = $url; //二维码内容
    $errorCorrectionLevel = 'L'; //容错级别
    $matrixPointSize      = 5; //生成图片大小
    $qrcode_dir           = 'qrcode';
    $PNG_WEB_DIR          = './' . $qrcode_dir;
    if (!file_exists($PNG_WEB_DIR)) {
        mkdir($PNG_WEB_DIR, 0777, true);
    }
    //生成二维码图片
    $filename = 'qrcode.png';
    QRcode::png($value, $PNG_WEB_DIR . '/' . $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    $QR      = basename($filename); //已经生成的原始二维码图片文件
    $img_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $qrcode_dir . '/' . $QR;

    return [
        'filepath' => '/' . $qrcode_dir . '/' . $QR,
        'img_url'  => $img_url,
    ];
}


/**
 * [将Base64图片转换为本地图片并保存]
 * @E-mial wuliqiang_aa@163.com
 * @TIME   2017-04-07
 * @WEB    http://blog.iinu.com.cn
 * @param  [Base64] $base64_image_content [要保存的Base64]
 * @param  [目录] $path [要保存的路径]
 */
function base64_image_content($base64_image_content){
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
        $type = $result[2];
        $new_dir = '.'.config('base64_upload_path');
        if(!file_exists($new_dir)){
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($new_dir, 0777, true);
        }
        $new_file_src = $new_dir."base64_test".".{$type}";
        if (file_put_contents($new_file_src, base64_decode(str_replace($result[1], '', $base64_image_content)))){
            return $new_file_src;
        }else{
            return false;
        }
    }else{
        return false;
    }
}
