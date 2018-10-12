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
		//前台测试链接：http://www.zhuqian.com/bxdj/api/v1_0_0/share/share.html?user_id=1&encryptedData=0a53bf188436d7372adfa7e613217f01&iv=1&share_type=2
		//share_type为2正常分享到群的逻辑。 share_type为6 签到水滴分享到群触发翻逻辑
		require_params('openid','encryptedData', 'iv', 'share_type'); 
		$data = Request::param();
		//获得的user_id查询转换成openid下
		$UserModel = new UserModel();
		$user = $UserModel->where('openid',$data['openid'])->find();
		if(!$user){
			return ['code' => 0, 'message' => '分享人不存在'];
		}
		//正常分享逻辑
		if($data['share_type'] == 2){

				$shareService = new ShareService();
				$result = $shareService->share($data);

				return result(200, '0k', $result);

	   //签到水滴分享到群触发翻倍接口
		}else if(($data['share_type'] == 6)){
			//获取该玩家今天的签到水滴
			//php获取今日开始时间戳和结束时间戳
			$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
			$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

			$qdjl_drop = Db::name('step')->where(['openid'=>$data['openid'],'comment'=>'签到奖励'])->where('create_time','between',[$beginToday,$endToday])->find();

			$double_steps = 0;
			if($qdjl_drop){
				$double_steps = $qdjl_drop['steps']*2;
				Db::name('step')->where(['id'=>$qdjl_drop['id'],'status'=>0])->update(['steps'=>$double_steps,'status'=>2]);
			}
			
			return result(200, '0k', ['double_steps'=>$double_steps]);

		}

		
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

		if($share_person['openid'] == $data['openid']){
			return ['message'=>'分享人为自己','code'=>1011];
		}

		//$is_old = $UserModel->where('openid',$data['openid'])->find(); //判断被分享人是否是新用户
		//相互分享
		if(Db::name('share_record')->where(['share_openid'=>$data['openid'],'click_openid'=>$share_person['openid']])->find()){
			return ['message'=>'不能玩家之间相互分享','code'=>1002];
		};

		$hasOrNot = Db::name('share_record')->where('click_openid',$data['openid'])->find();

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

		if(empty($invitees)){
			$result['invitees'] = [];
			$result['rate'] = 100;
			$result['nums'] = 0;
			return result(200, 'ok', $result);
		}
   
    	$invitee_imgs = array();
    	foreach ($invitees as $k => $v) {
    		$avatar = Db::name('user')->where('openid',$v['click_openid'])->find();
    		$invitees_data[$k]['img']=$avatar['avatar'];
    		$invitees_data[$k]['nickname']=$avatar['nickname'];
    	}
    	$result['invitees'] = $invitees_data;
       

	    //查询其当天未兑换步数应该的兑换比例；比例使用的是昨天与昨天之前的成功邀请的玩家的数量*配置比例

		//直接插缓存中数据即可，在user/index接口有存入各用户的缓存兑换比例记录
      	$cache_data = Cache::get($openid);
      	$result['rate'] = $cache_data['exchange_step_rate']*100;
	    //查询步数兑换比例END
	    
   		$nums = count($invitees);
        $result['nums'] = $nums;

       return result(200, 'ok', $result);


	}

}