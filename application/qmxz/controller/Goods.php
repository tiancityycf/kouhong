<?php
namespace app\qmxz\controller;

use app\qmxz\model\GoodCates as GoodCatesModel;
use app\qmxz\model\GoodDetails as GoodDetailsModel;
use app\qmxz\model\GoodImgs as GoodImgsModel;
use app\qmxz\model\Goods as GoodsModel;
use controller\BasicAdmin;
use think\Db;
use think\facade\Cache;

class Goods extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'goods';

    public function index()
    {
        $this->title = '商品管理';

        list($get, $db) = [$this->request->get(), new GoodsModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);

        $this->assign('title', $this->title);

        $GoodCatesModel = new GoodCatesModel();

        $cates = $GoodCatesModel->where(['status' => 1])->select();

        $this->assign('cates', $cates);

        return $this->fetch('index', $result);
    }

    /**
     * 添加商品
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function add()
    {

        $data = $this->request->post();

        if ($data) {
            //echo "<pre>"; print_r($data);exit();
            $arr                = [];
            $arr                = $data;
            $arr['create_time'] = time();

            if (Db::name($this->table)->strict(false)->insert($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }

        }
        $GoodCatesModel = new GoodCatesModel();

        $cates = $GoodCatesModel->where(['status' => 1])->select();

        $this->assign('cates', $cates);

        return $this->fetch('form', ['vo' => $data]);
    }

    /**
     * 编辑商品
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function edit()
    {
        $get_data = $this->request->get();

        $vo = Db::name($this->table)->where('id', $get_data['id'])->find();

        $post_data = $this->request->post();
        //dump($post_data);die;
        if ($post_data) {

            $arr                = [];
            $arr                = $post_data;
            $arr['update_time'] = time();

            if (Db::name($this->table)->where(['id' => $get_data['id']])->update($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        $GoodCatesModel = new GoodCatesModel();

        $cates = $GoodCatesModel->where(['status' => 1])->select();

        $this->assign('cates', $cates);

        return $this->fetch('edit', ['vo' => $vo]);
    }

    //刷新配置的时候一次存入缓存
    public function redisSave()
    {

        $redis = Cache::init();

        $goods_info = [];

        $good_cates = Db::name('good_cates')->where(['status' => 1])->order('order desc')->select();

        foreach ($good_cates as $k => $v) {
            // $arr = Db::name('good_cates')->alias('a')->join(['t_goods' => 'b'], 'a.id=b.cate')->where(['b.status' => 1, 'onsale' => 1, 'a.id' => $v['id']])->order('b.order desc')->column('b.id, a.cate_name, b.title, b.img, b.stock, b.price', 'a.id');
            $arr = Db::name('good_cates')->alias('a')->join(['t_goods' => 'b'], 'a.id=b.cate')->where(['b.status' => 1, 'a.id' => $v['id']])->order('b.order desc')->column('b.id, a.cate_name, b.title, b.is_partner, b.partner_appid, b.jump_route, b.img, b.stock, b.price', 'a.id');

            foreach ($arr as $k2 => $v2) {
                $arr[$k2]['imgs']         = Db::name('good_imgs')->where('product_id', $v2['id'])->select();
                $arr[$k2]['good_details'] = Db::name('good_details')->where('product_id', $v2['id'])->select();
            }

            $goods_info[$k]['info']      = $arr;
            $goods_info[$k]['cate_name'] = $v['cate_name'];

        }
        if (Cache::set(config('goods_info'), $goods_info)) {
            return 'success';
        } else {
            return 'fail';
        }

    }

    /**
     * 商品图片集
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function add_pics()
    {

        $get_data = $this->request->get();

        $model = new GoodImgsModel();

        $vo = $model->where('product_id', $get_data['id'])->select();

        $post_data = $this->request->post();
        //dump($post_data);die;
        if ($post_data) {

            $arr               = [];
            $arr               = $post_data;
            $arr['product_id'] = $get_data['id'];

            if ($model->save($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return $this->fetch('add_pics', ['vo' => $vo]);
    }

    /**
     * 商品详情
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function goods_details()
    {

        $get_data = $this->request->get();

        $model = new GoodDetailsModel();

        $vo = $model->where('product_id', $get_data['id'])->select();

        $post_data = $this->request->post();

        if ($post_data) {
            $arr               = [];
            $arr               = $post_data;
            $arr['product_id'] = $get_data['id'];

            if ($model->save($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return $this->fetch('goods_details', ['vo' => $vo]);
    }

    /**
     * 删除商品图片集
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function del_pic()
    {
        $product_id = $this->request->get('productId');

        if (!$product_id) {
            return;
        }

        $model = new GoodImgsModel();

        $res = $model->where('id', $product_id)->delete();

        if ($res && $this->redisSave()) {

            echo 'success';
        } else {

            echo 'fail';
        }

    }

    /**
     * 删除商品详情图片集
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function del_good_details_pic()
    {
        $product_id = $this->request->get('productId');

        if (!$product_id) {
            return;
        }

        $model = new GoodDetailsModel();

        $res = $model->where('id', $product_id)->delete();

        if ($res && $this->redisSave()) {

            echo 'success';
        } else {

            echo 'fail';
        }

    }

}
