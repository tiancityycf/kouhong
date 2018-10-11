<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Goods as GoodsModel;
use think\cache\driver\Redis;
use think\facade\Cache;

use app\bxdj\model\GoodImgs as GoodImgsModel;

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

        return  $this->fetch('index', $result);
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
            $arr = [];
            $arr = $data;
            $arr['create_time'] = time();

            if (Db::name($this->table)->strict(false)->insert($arr) !== false && $this->redisSave()) 
            {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }

        }
        return  $this->fetch('form', ['vo' => $data]);
    }

     /**
     * 编辑商品
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = Db::name($this->table)->where('id',$get_data['id'])->find();

        $post_data = $this->request->post();
        //dump($post_data);die;
        if ($post_data) {
                
            $arr = [];
            $arr = $post_data;
            $arr['update_time'] = time();

            if (Db::name($this->table)->where(['id' => $get_data['id']])->update($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('edit', ['vo' => $vo]);
    }

    //刷新配置的时候一次存入缓存
    public function redisSave()
    {
        $redis = Cache::init();

        $arr = Db::name($this->table)->where(['status' => 1, 'onsale' => 1])->order('order desc')->column('id, cate, title, img, stock, price', 'id');

        foreach ($arr as $k => $v) {
            $arr[$k]['imgs'] = Db::name('good_imgs')->where('product_id',$v['id'])->select();
        }

        //1.新人换礼 2.邀请专区 3.精品好礼;
        foreach ($arr as $k => $v) {
            if($v['cate'] == 1){
                 $v['cate'] = '新人换礼';
                 $goods_info['xrhl'][] =$v;
            }else if($v['cate'] == 2){
                $v['cate'] = '邀请专区';
                $goods_info['yqzq'][] =$v;
            }else{
                $v['cate'] = '精品好礼';
                $goods_info['jphl'][] =$v;
            }
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

        $vo = $model->where('product_id',$get_data['id'])->select();

        $post_data = $this->request->post();
        //dump($post_data);die;
        if ($post_data) {
                
            $arr = [];
            $arr = $post_data;
            $arr['product_id'] = $get_data['id'];

            if ($model->save($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('add_pics', ['vo' => $vo]);
    }

     /**
     * 删除商品图片集
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

      public function del_pic()
    {
        $product_id = $this->request->get('productId');

        if(!$product_id) return;

        $model = new GoodImgsModel();

        $res = $model->where('id',$product_id)->delete();

        if($res && $this->redisSave()){

            echo 'success';
        }else{

            echo 'fail';
        }
        
    }

  
}