<?php
namespace app\szctg\controller;

use controller\BasicAdmin;
use service\DataService;
use model\Complain as ComplainModel;
use app\api\service\Complain as ComplainService;
use zhise\HttpClient;

class Complain extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'complain';

    public function index()
    {
    	$this->title = '投诉管理';

       	list($get, $db) = [$this->request->get(), new ComplainModel()];

        $db = $db->search($get);
       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@complain/index', $result);
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $data = $this->request->post();
        $model = ComplainModel::get($data['id']);
        if ($data && $model) {
        	$model->userRecord->user_status = 0;
        	if ($model->userRecord->save() !== false) {
        		$this->success("拉黑成功！", '');
        	}
        }
        $this->error("拉黑失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $data = $this->request->post();
        $model = ComplainModel::get($data['id']);
        if ($data && $model) {
        	$model->userRecord->user_status = 1;
        	if ($model->userRecord->save() !== false) {
        		$this->success("恢复成功！", '');
        	}
        }
        $this->error("恢复失败，请稍候再试！");
    }
}