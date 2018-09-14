<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

use app\bxdj\model\StepCoinLog as StepCoinLogModel;

class StepCoinLog extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'step_coin_log';

	public function index()
    {

    	$this->title = '燃力币日志';

       	list($get, $db) = [$this->request->get(), new StepCoinLogModel()];

        $db = $db->search($get);
        
       	$result = parent::_list($db, true, false, false);

        $this->assign('title', $this->title);

        return  $this->fetch('index',$result);
    }

  
}