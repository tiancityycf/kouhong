<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Message as MessageModel;
use think\cache\driver\Redis;
use think\facade\Cache;

class Message extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'Message';

	public function index()
    {

    	$this->title = '消息中心管理';

       	list($get, $db) = [$this->request->get(), new MessageModel()];

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

        if (Cache::set(config('message_info'), $arr)) {
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
  
}