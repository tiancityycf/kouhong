<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Activity as ActivityModel;
use app\bxdj\model\Group as GroupModel;
use app\bxdj\model\GroupPersons as GroupPersonsModel;
use app\bxdj\model\RedpacketLog as RedpacketLogModel;
use think\cache\driver\Redis;
use think\facade\Cache;

class Group extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'Group';

	public function index()
    {

    	$this->title = '团队步行活动页';

       	list($get, $db) = [$this->request->get(), new GroupModel()];

        $db = $db->search($get);
        
       	$result = parent::_list($db, true, false, false);

        $this->assign('title', $this->title);

        return  $this->fetch('index', $result);
    }


    public function group_persons()
    {
        $group_id = $this->request->get('id');
        if($group_id){

          $groupPersonsModel = new GroupPersonsModel();
          $redpacket_info = $RedpacketLogModel->where('group_id',$group_id)->select();

        }

        return  $this->fetch('group_persons', ['vo' => $group_persons,'redpacket_info' => $redpacket_info]);
    }

     public function count_proportion()
    {

        $activityModel = new ActivityModel();
        //查看奖项的设置情况  根据设置的奖项数量来查询数据条数
        $reward_json = $activityModel->field('reward')->where(['status'=>0])->find();
        $reward_arr = json_decode($reward_json['reward'],true);
        $limit = count($reward_arr);

        //查询最新创建的开启的活动 并计算活动中前奖项设置数量的团队成员的贡献步数比例

        $groups = $activityModel->alias('a')->join('t_group g','a.id = g.activity_id','right')->where(['a.status'=>0])->order('group_steps desc')->limit($limit)->select();

        if($groups){
                $groupPersonsModel = new GroupPersonsModel();
                //循环所有的组
                foreach ($groups as $k => $v) {
                    //找寻group中的所有成员
                    $group_persons = $groupPersonsModel->where('group_id',$v['id'])->select();
                    foreach ($group_persons as $k2 => $v2) {
                        //计算各成员的捐献比例
                        $proportion = number_format($v2['contribute_steps']/$groups[$k]['group_steps'], 2);
                        //请留意这里获取的数据是对应的前三名的奖励
                        $reward = json_decode($groups[$k2]['reward'],true);

                        $get_reward = number_format($reward[$k+1] * $proportion,2);

                        $update_data = [
                            'proportion' => $proportion,
                            'get_reward' => $get_reward
                        ];
                        $res = $groupPersonsModel->where('id',$v2['id'])->update($update_data);
                    }

                }
                if($res !== false){
                    echo 'success';
                }else{
                    echo 'fail';
                }
                
        }else{

             echo '请联系程序';
        }


        
    }
    
  
  
}