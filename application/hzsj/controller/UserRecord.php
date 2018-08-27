<?php
namespace app\hzsj\controller;

use controller\BasicAdmin;
use service\DataService;
use model\User as UserModel;
use model\Heimingdan as HeimingdanModel;

class UserRecord extends BasicAdmin
{

	public function index()
    {
    	$this->title = '用户数据';

       	list($get, $db) = [$this->request->get(), new UserModel()];

        $db = $db->search($get);
       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@user_record/index', $result);
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $data = $this->request->post();
        $model = UserModel::get($data['id']);
        if ($data && $model) {
        	$model->userRecord->user_status = 0;
        	if ($model->userRecord->save() !== false) {
                if ($model->userRecord->nickname) {
                    $heimingdan = HeimingdanModel::where('nickname', $model->userRecord->nickname)->find();
                    if ($heimingdan) {
                        $heimingdan->status = 0;
                    } else {
                        $heimingdan = new HeimingdanModel();
                        $heimingdan->nickname = $model->userRecord->nickname;
                    }

                    $heimingdan->save();
                }
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
        $model = UserModel::get($data['id']);
        if ($data && $model) {
        	$model->userRecord->user_status = 1;
        	if ($model->userRecord->save() !== false) {
        		$this->success("恢复成功！", '');
        	}
        }
        $this->error("恢复失败，请稍候再试！");
    }
}