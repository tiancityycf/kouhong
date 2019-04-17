<?php

namespace service;

use think\Db;
use Wcs\Http\PutPolicy;
use Wcs\Upload\Uploader;

class WangSuService
{
	public function upload($file_arr)
	{
		//$file_arr = $_FILES;
        //$file_arr = $file_arr['file'];
        
        if($file_arr['error'] > 0) {
            return ajax_return_error(['error_msg'=>$file_arr['error']]);
        }
        
        $suffix_name = substr(strrchr($file_arr['name'], '.'), 1); 
        
        $time = time();
        $rand_str = md5(date('YmdHis',$time).mt_rand(0, 9999));
        
        $file_name = $rand_str.'.'.$suffix_name;
        $bucketName = config('bucketName');//bucketName 空间名称
        $fileKey = config('folder').$file_name;//fileKey   自定义文件名
        $localFile = $file_arr['tmp_name'];//localFile 上传文件名
        $returnBody = '';//returnBody    自定义返回内容  (可选）
        $userParam = '';//userParam 自定义变量名    <x:VariableName>    (可选）
        $userVars = '';//userVars  自定义变量值    <x:VariableValue>   (可选）
        $mimeType = '';//mimeType  自定义上传类型  (可选）
        
        $pp = new PutPolicy();
        if ($fileKey == null || $fileKey === '') {
            $pp->scope = $bucketName;
        } else {
            $pp->scope = $bucketName . ':' . $fileKey;
        }
        $pp->returnBody = '';
        $pp->deadline = '';//单位为毫秒
        $token = $pp->get_token();
        
        $client = new Uploader($token, $userParam, $userVars, $mimeType);
        $resp = $client->upload_return($localFile);
        
        if($resp->code == 200){
            $img_url = config('img_url_config').$fileKey;//详细访问地址
            /*$insert = [
                'cate'     => 3,
                'name'     => $img_url,
                'original' => $file_arr['name'],
                'domain'   => '',
                'type'     => $file_arr['type'],
                'size'     => $file_arr['size'],
                'mtime'    => $time,
            ];*/
            //Db::name('File')->insert($insert);
            
            return json_encode(['path' => $img_url, 'code' => 1, 'size' => $file_arr['size'], 'type' => $file_arr['type']]);
        }
        return json_encode(['error_msg'=>$resp->respBody, 'code' => 2]);
	}
}