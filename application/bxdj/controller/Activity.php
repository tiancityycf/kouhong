<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Activity as ActivityModel;
use think\cache\driver\Redis;
use think\facade\Cache;

class Activity extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'activity';

	public function index()
    {

    	$this->title = '活动管理';

       	list($get, $db) = [$this->request->get(), new ActivityModel()];

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

            if (Db::name($this->table)->where(['id' => $get_data['id']])->update($arr) !== false && $this->redisSave()) {
                //若活动设置为结束
                if($arr['status'] == 0){
                    //1.将所有活动的组设置为结束
                    Db::name('group')->where(['activity_id' => $get_data['id']])->update(['status'=>0]);
                    //2.更新所有组的排名信息
                    $rank = Db::name('group')->where(['activity_id' => $get_data['id']])->order('group_steps desc')->select();
                    foreach ($rank as $k => $v) {
                        Db::name('group')->where(['id' => $v['id']])->update(['rank'=>$k+1]);
                    }
                }
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
            if(strpos($v['description'],'|')){
                $arr[$k]['description'] = explode('|', $v['description']);
            }
        }

        if (Cache::set(config('activity_info'), $arr)) {
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