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
	 * 创建地址信息
	 * @return boolean
	 */
	public function create_addr()
	{
		//前台测试链接：https://khj.wqop2018.com/khj/api/v1_0_1/address/create_addr.html?openid=1&nickname=kevin&phone=15888888888&addr=长沙岳麓区&region=湖南;
		require_params('user_id', 'nickname', 'phone', 'addr','region');
		$data = Request::param();
	
//		$data['create_time'] = time();
//        $AddressModel = new AddressModel();
//        $where['user_id'] = $data['user_id'];
//        $exists = $AddressModel->where($where)->find();
//        if(empty($exists)){
//            $result = $AddressModel->save($data);
//        }else{
//            $result = $AddressModel->save($data,$where);
//        }
//        return result(200, 'ok', $result);
	}

}