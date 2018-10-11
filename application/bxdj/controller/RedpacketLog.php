<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

use app\bxdj\model\RedpacketLog as RedpacketLogModel;
use think\cache\driver\Redis;
use think\facade\Cache;

class RedpacketLog extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'Group';

	public function index()
    {

    	$this->title = '团队步行获奖名单页';

       	list($get, $db) = [$this->request->get(), new RedpacketLogModel()];

        $db = $db->search($get);
        
       	$list = parent::_list($db, true, false, false);

        $this->assign('title', $this->title);

        return  $this->fetch('index', $list);
    }

    public function set_payed()
    {
        $data = $this->request->get();

        $model = new RedpacketLogModel();

        if ($data['id'] && $model) {
           $res = $model->where('id',$data['id'])->update(['status'=>1]);
           if($res){
                $this->success("状态改为已支付", '');
           }    
        }
        $this->error("操作失败，请稍候再试！");
    }
  
}