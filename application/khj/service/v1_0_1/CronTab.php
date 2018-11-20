<?php
namespace app\khj\service\v1_0_1;

use app\khj\model\GoodCates as GoodCatesModel;
use app\khj\model\Goods as GoodsModel;
use think\Db;
use think\facade\Config;
use Wcs\Http\PutPolicy;
use Wcs\Upload\Uploader;

/**
 * 脚本服务类
 */
class CronTab
{
    protected $configData;

    public function __construct($configData)
    {
        $this->configData = $configData;
    }

    /**
     * 抓取商品信息脚本
     * @return [type] [description]
     */
    public function captureData()
    {
        //抓包地址
        $capture_data_url = Config::get('capture_data_url');
        $get_data         = json_decode(file_get_contents($capture_data_url), true);
        if ($get_data['errno'] == 0) {
            $data = $get_data['data'];
            if (!empty($data)) {
                // 开启事务
                Db::startTrans();
                try {
                    foreach ($data as $key => $value) {
                        //检查分类是否存在
                        $cate_info = GoodCatesModel::where('cate_name', $value['title'])->find();
                        if ($cate_info) {
                            //存在
                            //检查数据是否存在
                            $goods_info = GoodsModel::where('title', $value['model'])->find();
                            if ($goods_info) {
                                continue;
                            } else {
                                //添加商品数据
                                if ($value['thumb'] == '') {
                                    continue;
                                }
                                $img_info = $this->Upload($value['thumb']);
                                if ($img_info['code'] != 1) {
                                    continue;
                                }
                                $goods             = new GoodsModel();
                                $goods->cate       = $cate_info['id'];
                                $goods->title      = $value['model'];
                                $goods->img        = $img_info['img_url'];
                                $goods->sale_price = $value['storeprice'];
                                $goods->price      = $value['price'];
                                $goods->order      = $value['sort'];
                                $goods->save();
                                Db::commit();
                            }
                        } else {
                            //不存在
                            //添加分类
                            $good_cates            = new GoodCatesModel();
                            $good_cates->cate_name = $value['title'];
                            $good_cates->banner    = '';
                            $good_cates->save();
                            //添加商品数据
                            if ($value['thumb'] == '') {
                                continue;
                            }
                            $img_info = $this->Upload($value['thumb']);
                            if ($img_info['code'] != 1) {
                                continue;
                            }
                            $goods             = new GoodsModel();
                            $goods->cate       = $good_cates->id;
                            $goods->title      = $value['model'];
                            $goods->img        = $img_info['img_url'];
                            $goods->sale_price = $value['storeprice'];
                            $goods->price      = $value['price'];
                            $goods->order      = $value['sort'];
                            $goods->save();
                            Db::commit();
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    lg($e);
                    Db::rollback();
                }
            }
            return [
                'status' => 1,
                'msg'    => 'ok',
            ];
        } else {
            trace($get_data['message'], 'error');
            return [
                'status' => 0,
                'msg'    => $get_data['message'],
            ];
        }
    }

    /**
     * 把远程图片上传到另一个站点
     * @return [type] [description]
     */
    public function Upload($img = '')
    {
        if (empty($img)) {
            return ['error_msg' => '地址为空', 'code' => 2];
        }
        $content     = file_get_contents($img);
        $file_path   = file_put_contents(dirname(__FILE__) . '\path.jpg', $content);
        $suffix_name = substr(strrchr($img, '.'), 1);
        $time        = time();
        $rand_str    = md5(date('YmdHis', $time) . mt_rand(0, 9999));

        $file_name  = $rand_str . '.' . $suffix_name;
        $bucketName = config('bucketName'); //bucketName 空间名称
        $fileKey    = 'khj/' . $file_name; //fileKey   自定义文件名
        $localFile  = dirname(__FILE__) . '\path.jpg'; //localFile 上传文件名
        $returnBody = ''; //returnBody    自定义返回内容  (可选）
        $userParam  = ''; //userParam 自定义变量名    <x:VariableName>    (可选）
        $userVars   = ''; //userVars  自定义变量值    <x:VariableValue>   (可选）
        $mimeType   = ''; //mimeType  自定义上传类型  (可选）

        $pp = new PutPolicy();
        if ($fileKey == null || $fileKey === '') {
            $pp->scope = $bucketName;
        } else {
            $pp->scope = $bucketName . ':' . $fileKey;
        }
        $pp->returnBody = '';
        $pp->deadline   = ''; //单位为毫秒
        $token          = $pp->get_token();

        $client = new Uploader($token, $userParam, $userVars, $mimeType);
        $resp   = $client->upload_return($localFile);

        if ($resp->code == 200) {
            unlink(dirname(__FILE__) . '\path.jpg');
            $img_url = config('img_url_config') . $fileKey; //详细访问地址
            return ['img_url' => $img_url, 'code' => 1];
        }
        return ['error_msg' => $resp->respBody, 'code' => 2];
    }
    
}
