<?php

namespace app\bxdj\controller\api\v1_0_0;

use think\Db;
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
	 * 分享到群触发接口
	 * @return json
	 */
	public function share()
	{
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/share/share.html?user_id=1&encryptedData=0a53bf188436d7372adfa7e613217f01&iv=1&share_type=1
		require_params('openid','encryptedData', 'iv', 'share_type'); 
		$data = Request::param();
		//获得的user_id查询转换成openid下
		$UserModel = new UserModel();
		$user = $UserModel->where('openid',$data['openid'])->find();
		if(!$user){
			return ['code' => 0, 'message' => '分享人不存在'];
		}

		$shareService = new ShareService();
		$result = $shareService->share($data);

		return result(200, '0k', $result);
	}

	//分享到个人用户点击后的回调执行方法
	public function share_record()
	{
		//前台测试链接：https://hz.zxmn2018.com/bxdj/api/v1_0_0/share/share_record.html?user_id=9&openid=1
		require_params('user_id', 'openid'); //user_id 是分享人id；openid是被分享人信息
		$data = Request::param();
		$UserModel = new UserModel();
		$share_person = $UserModel->where('id',$data['user_id'])->find();
		if(!$share_person){
			return ['message'=>'分享人不存在','code'=>1010];
		}
		
		$hasOrNot = $UserModel->where('openid',$data['openid'])->find(); //判断被分享人是否是新用户

		if(empty($hasOrNot)){
				//1.是新用户
				//获取配置信息
		        $share = new \api_data_service\v1_0_0\Share();
		        //插入信息到分享记录表
		        $insert_data = [
		        	'share_openid' => $share_person['openid'],
		        	'share_time' => time(),
		        	'share_date' => date('Y-m-d',time()),
		        	'click_status' =>1,
		        	'click_openid' => $data['openid'],
		        ];
		        Db::name('share_record')->insert($insert_data);
		        return $share->shareUser($share_person['openid']);
		}else{
				//2.分享的为老用户
				return ['message'=>'该用户为老用户','code'=>1000];

		}
	}

	/**
	 * 查看所有该用户邀请的玩家
	 * @return json
	 */
	public function all_invitee(){

		require_params('openid');
        $openid = Request::param('openid');

		$invitees = Db::name('share_record')->where('share_openid',$openid)->order('share_time desc')->select();
   
    	$invitee_imgs = array();
    	foreach ($invitees as $k => $v) {
    		$avatar = Db::name('user')->where('openid',$v['click_openid'])->find();
    		$invitees_data[$k]['img']=$avatar['avatar'];
    		$invitees_data[$k]['nickname']=$avatar['nickname'];
    	}
    	$result['invitees'] = $invitees_data;
       

		//获取缓存信息
	    $config = Cache::get(config('config_key'));

	    //获取配置的邀请新玩家数量—增加步数的比例	
	    $config_rate = $config['nums_to_rate']['value'];
	    $nums = count($invitees);

	    $result['rate'] = 100 + $config_rate*$nums;
   
        $result['nums'] = $nums;

       return result(200, 'ok', $result);


	}

}