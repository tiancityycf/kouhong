<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Goods as GoodsModel;
use think\cache\driver\Redis;
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

     protected function redisSave()
    {
        $redis = Cache::init();

        $arr = Db::name($this->table)->where(['status' => 1, 'onsale' => 1])->column('cate, title, img, stock, price', 'id');

        if (Cache::set(config('goods_info'), $arr)) {
            return true;
        } else {
            return false;
        }

    }


  
}