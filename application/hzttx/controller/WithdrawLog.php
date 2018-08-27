<?php
namespace app\hzttx\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use model\WithdrawLog as WithdrawLogModel;

class WithdrawLog extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'withdraw_log';

    public function index()
    {
    	$this->title = '提现记录';

       	list($get, $db) = [$this->request->get(), new WithdrawLogModel()];

        $db = $db->search($get);

        $this->assign('get', $get);
       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@withdraw_log/index', $result);
    }


    public function pay()
    {
    	/*if (DataService::update($this->table)) {
            $this->success("支付成功！", '');
        }
        $this->error("支付失败，请稍候再试！");*/

        $data = $this->request->post();

        $user = session('user');

        $model = WithdrawLogModel::get($data['id']);

        if ($model) {
        	$model->status = 1;
        	$model->pay_time = time();
        	$model->admin_user_id = $user['id'];

        	if ($model->save()) {
        		$this->success("支付成功！", '');
        	}
        }

        $this->error("支付失败，请稍候再试！");

    }
}