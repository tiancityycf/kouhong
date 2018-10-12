<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Activity as ActivityModel;
use app\bxdj\model\GroupPersons as GroupPersonsModel;
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
                    //3.刷新活动中奖项设置的人员的获奖情况

                    $res = $this->count_proportion($get_data['id']);
                    if(!$res){
                        $this->error('数据保存失败, 请联系程序员!');
                    }
                }
              
                $this->success('恭喜, 数据保存成功!', '');
                
                
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('edit', ['vo' => $vo]);
    }


    //3.刷新活动中奖项设置的人员的获奖情况
     public function count_proportion($activity_id)
    {

        $activityModel = new ActivityModel();
        //查看奖项的设置情况  根据设置的奖项数量来查询数据条数
        $reward_json = $activityModel->field('reward')->where('id',$activity_id)->find();
        $reward_arr = json_decode($reward_json['reward'],true);
        $limit = count($reward_arr);

        //查询最新创建的开启的活动 并计算活动中前奖项设置数量的团队成员的贡献步数比例

        $groups = $activityModel->alias('a')->join('t_group g','a.id = g.activity_id','right')->where(['a.id'=>$activity_id])->order('group_steps desc')->limit($limit)->select();
    
        if($groups){
                $groupPersonsModel = new GroupPersonsModel();
                //循环所有的组
                foreach ($groups as $k => $v) {
                    //找寻group中的所有成员
                    $group_persons = $groupPersonsModel->where('group_id',$v['id'])->select();
                    foreach ($group_persons as $k2 => $v2) {
                     
                        //计算各成员的捐献比例
                        if($groups[$k]['group_steps'] != 0){
                            $proportion = number_format($v2['contribute_steps']/$groups[$k]['group_steps'], 2);
                        }else{
                            $proportion = 0;
                        }
                        
                        //请留意这里获取的数据是对应的前三名的奖励
                        $reward = json_decode($groups[$k2]['reward'],true);
                        if($proportion != 0){
                            $get_reward = number_format($reward[$k+1] * $proportion,2,'.','');
                        }else{
                            $get_reward = 0;
                        }
                        

                        $update_data = [
                            'proportion' => $proportion,
                            'get_reward' => $get_reward
                        ];

                        $res = $groupPersonsModel->where('id',$v2['id'])->update($update_data);
                    }

                }
                if($res !== false){
                    return true;
                }else{
                    return false;
                }
                
        }else{

             return false;
        }
     
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