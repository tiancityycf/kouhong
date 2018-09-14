<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\facade\Request;
use api_data_service\v1_0_0\Share as ShareService;
use model\User as UserModel;

use think\facade\Config;
use think\facade\Cache;
/**
 * 用户分享控制器类
 */
class Share
{
	/**
	 * 分享
	 * @return json
	 */
	public function share()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/share/share.html?user_id=1&encryptedData=0a53bf188436d7372adfa7e613217f01&iv=1&share_type=1
		require_params('user_id','encryptedData', 'iv', 'share_type'); 
		$data = Request::param();

		//获得的user_id查询转换成openid下
		$UserModel = new UserModel();
		$user = $UserModel->where('id',$data['user_id'])->find();
		unset($data['user_id']);
		$data['openid'] = $user['openid'];

		$shareService = new ShareService();
		$result = $shareService->share($data);

		return result(200, '0k', $result);
	}

	//分享用户点击后的回调执行方法
	public function share_record()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/share/share.html?user_id=1&encryptedData=0a53bf188436d7372adfa7e613217f01&iv=1&share_type=1
		require_params('user_id', 'openid'); //user_id 是分享人id；openid是被分享人信息
		$data = Request::param();

		//获得的user_id查询转换成openid下
		$UserModel = new UserModel();
		$hasOrNot = $UserModel->where('openid',$data['openid'])->find(); //判断被分享人是否是新用户

		if(empty($hasOrNot)){
				//1.是新用户
				//获取配置信息
		        $share = new \api_data_service\v1_0_0\Share();
		        return $share->shareUser($data);
		}else{
				//2.分享的为老用户
				

		}

		$user = $UserModel->where('id',$data['user_id'])->find();


		unset($data['user_id']);
		$data['openid'] = $user['openid'];

		$shareService = new ShareService();
		$result = $shareService->share($data);

		return result(200, '0k', $result);
	}
}