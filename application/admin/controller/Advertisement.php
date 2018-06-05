<?php
namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use think\facade\Cache;

class Advertisement extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'advertisement';
    public $adv_key = 'xsc:829fc:advertisementlist';

    public function index()
    {
    	$this->title = "广告位管理";

    	$db = Db::name($this->table);

    	$this->assign('type', [1 => '广点通', 2 => '跳转到其他小程序']);
    	$this->assign('position_type', [1 => '首页浮动广告', 2 => '首页banner广告', 3=> '游戏页广告']);
    	$this->assign('version', ['develop' => '开发版', 'trial' => '体验版', 'release'=> '正式版']);

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
            $this->success("开启成功！", '');
        }
        $this->error("开启失败，请稍候再试！");
    }

     protected function redisSave()
    {
        $data = Db::name($this->table)->field('id as advertisement_id, type, open_ad, appid, path, position, xcx_img, position_type')->order('id', 'desc')->select();

        $redis = Cache::init();

        if (Cache::set($this->adv_key, $data)) {
            return true;
        } else {
            return false;
        }
    }

}