<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Dairy as DairyModel;
use think\cache\driver\Redis;
use think\facade\Cache;

class Dairy extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'dairy';

	public function index()
    {

    	$this->title = '燃力相册管理';

       	list($get, $db) = [$this->request->get(), new DairyModel()];

        //$db = $db->search($get);
        
       	$result = parent::_list($db, true, false, false);

        $this->assign('title', $this->title);

        return  $this->fetch('index', $result);
    }

    /**
     * 添加
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
        return  $this->fetch('form');
    }

     /**
     * 编辑
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

        $arr = Db::name($this->table)->select();

        foreach ($arr as $k => $v) {
            $arr[$k]['imgs'] = Db::name('dairy_imgs')->where('dairy_id',$v['id'])->field('img')->select();
        }

        if (Cache::set(config('dairy_info'), $arr)) {
            return true;
        } else {
            return false;
        }

    }

     /**
     * 删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function delete()
    {
        $get_data = $this->request->get();
        
        $res = Db::name($this->table)->where('id',$get_data['id'])->delete();

        if ($res && $this->redisSave()) {
            $this->success('成功删除数据!', '');
        } else {
            $this->error('删除数据失败!');
        }

    }

    /**
     * 燃力日记图册
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function add_imgs()
    {
        $get_data = $this->request->get();

        $vo = Db::name('dairy_imgs')->where('dairy_id',$get_data['id'])->select();

        $post_data = $this->request->post();
        //dump($post_data);die;
        if ($post_data) {
                
            $arr = [];
            $arr = $post_data;
            $arr['dairy_id'] = $get_data['id'];
            $arr['create_time'] = time();

            if (Db::name('dairy_imgs')->insert($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('add_imgs', ['vo' => $vo]);

    }


     /**
     * 删除商品图片集
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

      public function del_pic()
    {
        $picId = $this->request->get('picId');

        if(!$picId) return;

        $res = Db::name('dairy_imgs')->where('id',$picId)->delete();

        if($res && $this->redisSave()){

            echo 'success';
        }else{

            echo 'fail';
        }
        
    }


    
  
}