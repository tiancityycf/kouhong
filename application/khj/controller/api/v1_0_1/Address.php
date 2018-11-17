<?php

namespace app\khj\controller\api\v1_0_1;

use think\facade\Request;

use app\khj\model\Address as AddressModel;

/**
 * 用户分享控制器类
 */
class Address
{
	/**
	 * 显示用户的地址信息
	 * @return array
	 */

	 public function index()
	{
		//前台测试链接：http://khj.com/khj/api/v1_0_1/address/index.html?openid=1
		require_params('openid');
		$openid = Request::param('openid');
        $AddressModel = new AddressModel();
        $result = $AddressModel->where('openid',$openid)->order('status desc')->select();
        return result(200, 'ok', $result);
	}

	 /**
	 * 创建地址信息
	 * @return boolean
	 */
	public function create_addr()
	{
		//前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/address/create_addr.html?openid=1&nickname=kevin&phone=15888888888&addr=长沙岳麓区&region=湖南;
		require_params('user_id', 'nickname', 'phone', 'addr','region');
		$data = Request::param();
	
		$data['create_time'] = time();
        $AddressModel = new AddressModel();
        $where['user_id'] = $data['user_id'];
        $result = $AddressModel->save($data,$where);
        return result(200, 'ok', $result);
	}

	/**
	 * 设为默认地址信息
	 * @return boolean
	 */

	public function set_default_addr()
	{
		//前台测试链接：http://khj.com/khj/api/v1_0_1/address/set_default_addr.html?openid=1&id=3;
		require_params('openid', 'id');
		$data = Request::param();
        $AddressModel = new AddressModel();

        //先设所有该openid用户的地址为0
        $AddressModel->where('openid',$data['openid'])->update(['status'=>0]);

        //在设该openid用户指定地址为默认
        $result = $AddressModel->where(['id'=>$data['id'],'openid'=>$data['openid']])->update(['status'=>1]);
        return result(200, 'ok', $result);
	}

	/**
	 * 创建地址信息
	 * @return json or boolean
	 */
	public function edit_addr()
	{
		//前台测试链接：http://khj.com/khj/api/v1_0_1/address/edit_addr.html?id=3&openid=1&nickname=kevin&phone=15888888888&addr=长沙岳麓区&region=湖南&ty=getInfo;
		require_params('id','ty');
		$AddressModel = new AddressModel();
		$data = Request::param();
		if($data['ty'] == 'getInfo'){

			$result = $AddressModel->where('id',$data['id'])->find();
			return result(200, 'ok', $result);

		}else if($data['ty'] == 'setInfo'){

       	    unset($data['ty']);
      		$result = $AddressModel->where('id',$data['id'])->update($data);
        	return result(200, 'ok', $result);
		}	
		
	}

	/**
	 * 删除地址信息
	 * @return boolean
	 */
	public function del_addr()
	{
		//前台测试链接：http://khj.com/khj/api/v1_0_1/address/del_addr.html?id=7
		require_params('id');
		$id = Request::param('id');
        $AddressModel = new AddressModel();
        $result = $AddressModel->where('id',$id)->delete();
        return result(200, 'ok', $result);
	}
}