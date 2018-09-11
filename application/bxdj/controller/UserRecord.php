<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use model\User as UserModel;
use model\Heimingdan as HeimingdanModel;

use app\bxdj\model\UserRecord as UserRecordModel;
use app\bxdj\model\Address as AddressModel;

class UserRecord extends BasicAdmin
{

	public function index()
    {

    	$this->title = '用户数据';

       	list($get, $db) = [$this->request->get(), new UserRecordModel()];

        $db = $db->search($get);
        
       	$result = parent::_list($db, true, false, false);

        $this->assign('title', $this->title);

        return  $this->fetch('index', $result);
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

    /**
     * 用户收货地址信息
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function address()
    {
        $data = $this->request->get();

        $model = new AddressModel();

        if ($data['openid'] && $model) {
           $vo = $model->where('openid',$data['openid'])->select();
           return  $this->fetch('address', ['vo' => $vo]);
        }
        $this->error("查看地址信息失败，请稍候再试！");

    }


}