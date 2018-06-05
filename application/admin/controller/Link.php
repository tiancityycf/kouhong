<?php
namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use think\facade\Cache;

class Link extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'app_link';
    public $link_key = 'xsc:829fc:applinklist';

    public $position_arr = [1 => '更多好玩', 2 => '首页推广'];  //推广链接的位置

    public function index()
    {
    	$this->title = 'APP链接管理';

    	list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['app_title'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        $this->assign('position_arr', $this->position_arr);
    	return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if($data){
             if (Db::name($this->table)->strict(false)->insert($data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
    	
        $this->assign('position_arr', $this->position_arr);
        return  $this->fetch('form', ['vo' => $data]);
        //return $this->_form($this->table, 'form');
    }

    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = Db::name($this->table)->where('id',$get_data['id'])->find();

        $post_data = $this->request->post();
        if($post_data){
            if (Db::name($this->table)->where(['id' => $get_data['id']])->update($post_data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        $this->assign('position_arr', $this->position_arr);
        return  $this->fetch('form', ['vo' => $vo]);
        //return $this->_form($this->table, 'form');
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (DataService::update($this->table) && $this->redisSave()) {
            $this->success("禁用成功！", '');
        }
        $this->error("禁用失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        if (DataService::update($this->table) && $this->redisSave()) {
            $this->success("启用成功！", '');
        }
        $this->error("启用失败，请稍候再试！");
    }

    protected function redisSave()
    {
        $data = Db::name($this->table)->where('status', 1)->order('sort_order', 'desc')->select();

        $redis = Cache::init();

        if (Cache::set($this->link_key, $data)) {
            return true;
        } else {
            return false;
        }
    }
}