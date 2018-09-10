<?php
namespace app\shz\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use think\cache\driver\Redis;
use think\facade\Cache;

class Tpfig extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'config';

    public function index()
    {
    	$this->title = '配置管理';

    	list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['name'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }
        
        $result = parent::_list($db, true, false, false);
    	$this->assign('title', $this->title);
        return  $this->fetch('admin@tpfig/index', $result);
    }


    public function add()
    {
    	$data = $this->request->post();
    	if ($data) {
            //echo "<pre>"; print_r($data);exit();
            $arr = [];
            $arr['name'] = $data['name'];
            $arr['index'] = $data['index'];
            $arr['type'] = $data['type'];
            if ($data['type'] == 1) {
                $arr['value'] = $data['value'];
            } else {
                $arr['value'] = json_encode($data['value_arr'], JSON_UNESCAPED_UNICODE);
            }
            $arr['status'] = $data['status'];

            if (Db::name($this->table)->strict(false)->insert($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }

    	}

    	return  $this->fetch('admin@tpfig/form', ['vo' => $data]);
    }


    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = Db::name($this->table)->where('id',$get_data['id'])->find();

        $post_data = $this->request->post();
        if ($post_data) {
            $arr = [];
            $arr['name'] = $post_data['name'];
            $arr['index'] = $post_data['index'];
            $arr['type'] = $post_data['type'];
            if ($post_data['type'] == 1) {
                $arr['value'] = $post_data['value'];
            } else {
                $arr['value'] = json_encode($post_data['value_arr'], JSON_UNESCAPED_UNICODE);
            }
            $arr['status'] = $post_data['status'];

            if (Db::name($this->table)->where(['id' => $get_data['id']])->update($arr) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }



        return  $this->fetch('admin@tpfig/edit', ['vo' => $vo]);
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $data = $this->request->post();

        $config = Db::name($this->table)->where('id',$data['id'])->find();
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
        $data = $this->request->post();

        $config = Db::name($this->table)->where('id',$data['id'])->find();
        if (DataService::update($this->table) && $this->redisSave()) {
            $this->success("启用成功！", '');
        }
        $this->error("启用失败，请稍候再试！");
    }

    protected function redisSave()
    {
        $redis = Cache::init();

        $arr = Db::name($this->table)->where('status',1)->column('index, value, type', 'index');

        $readme = Db::name($this->table)->where('status',1)->where('index', 'readme')->find();

        if (Cache::set(config('config_key'), $arr) && Cache::set(config('readme_key'), json_decode($readme['value']))) {
            return true;
        } else {
            return false;
        }

    }

}