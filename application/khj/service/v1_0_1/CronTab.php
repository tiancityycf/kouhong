<?php
namespace app\khj\service\v1_0_1;

use app\khj\model\GoodCates as GoodCatesModel;
use app\khj\model\Goods as GoodsModel;
use think\Db;
use think\facade\Config;

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
                                $goods             = new GoodsModel();
                                $goods->cate       = $cate_info['id'];
                                $goods->title      = $value['model'];
                                $goods->img        = $value['thumb'];
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
                            $goods             = new GoodsModel();
                            $goods->cate       = $good_cates->id;
                            $goods->title      = $value['model'];
                            $goods->img        = $value['thumb'];
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
                'status' => 1,
                'msg'    => $get_data['message'],
            ];
        }
    }
}
