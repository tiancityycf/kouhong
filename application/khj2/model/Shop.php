<?php

namespace app\khj2\model;

use think\Model;

class Shop extends Model
{
	protected $connection = [
		// 数据库类型
		'type'            => 'mysql',
		// 服务器地址
		'hostname'        => 'localhost',
		// 数据库名
		'database'        => 'shop',
		// 用户名
		'username'        => 'shop',
		// 密码
		'password'        => '6TCEkmjhSsdZ8mBW',

		// 数据库表前缀
		'prefix'          => 'eb_',
		];
	protected $table = 'eb_user';

	public function add_score($params)
	{
		$query = self::buildQuery();
		$query->alias('u');

		$query->field('u.uid,u.is_promoter,u.spread_uid,u.promoter_uid,promoter_link');

		$query->join(['eb_user_games'=>'ur'],'ur.uid=u.uid');

		$query->where('ur.game_id', $params['game_id']);
		$query->where('ur.open_id', $params['openid']);

		$result = [];
		$result = $query->find();
		$score = $params['score'];

		if(!empty($result)){
			$params['uid'] = $result['uid'];
			$params['title'] = '活动获取';
			$params['mark'] = '活动获取了';
			/**
			  1.A绑定了商城 得了100分
			  假设A在商城是金牌  ，再判定有没有推荐人，不管有没有推荐人都得90（金牌会员的推荐人如果删了或者没有，则无操作。如果金牌代理的的推荐人存在，则推荐人获得金牌代理所有积分的10%）。
			  如果A在商城不是金牌 没有推荐人 则得75
			  如果A在商城不是金牌 有推荐人B（不是金牌） 则得75  B得15
			  如果A在商城不是金牌 有推荐人B（是金牌）  则得75  B得15+10
			  如果A在商城不是金牌 有推荐人B（不是金牌），还有金牌推荐人C  则得75  B得15  C得10
			 */

			if($result['is_promoter']){
				//假设A在商城是金牌  ，再判定有没有推荐人，不管有没有推荐人都得90
				$params['score'] = $score * 0.9;
				$this->score_add($params);
				if($result['spread_uid']){
					//如果金牌代理的的推荐人存在，则推荐人获得金牌代理所有积分的10%
					$params['score'] = $score * 0.1;
					$params['uid'] = $result['spread_uid'];
					$params['title'] = '推荐会员获取';
					$params['mark'] = '推荐会员获取了';
					$this->score_add($params);
				}
			}else{
				//如果A在商城不是金牌
				if(empty($result['spread_uid'])){
					//没有推荐人 则得75
					$params['score'] = $score * 0.75;
					$this->score_add($params);
				}else{
					$spread = self::table("eb_user")->where("uid",$result['spread_uid'])->find();
				        if(!empty($spread)){
						if($spread['is_promoter']){
							//有推荐人B（是金牌）  则得75  B得15+10
							$params['score'] = $score * 0.75;
							$this->score_add($params);
							$params['score'] = $score * 0.25;
							$params['uid'] = $result['spread_uid'];
							$params['title'] = '推荐会员获取';
							$params['mark'] = '推荐会员获取了';
							$this->score_add($params);
						}else{
							if($result['promoter_uid']){
								//有推荐人B（不是金牌），还有金牌推荐人C  则得75  B得15  C得10
								$params['score'] = $score * 0.75;
								$this->score_add($params);
								$params['score'] = $score * 0.15;
								$params['uid'] = $result['spread_uid'];
								$params['title'] = '推荐会员获取';
								$params['mark'] = '推荐会员获取了';
								$this->score_add($params);
								$params['score'] = $score * 0.1;
								$params['uid'] = $result['promoter_uid'];
								$params['title'] = '推荐会员获取';
								$params['mark'] = '推荐会员获取了';
								$this->score_add($params);
							}else{
								//有推荐人B（不是金牌） 则得75  B得15
								$params['score'] = $score * 0.75;
								$this->score_add($params);
								$params['score'] = $score * 0.15;
								$params['uid'] = $result['spread_uid'];
								$params['title'] = '推荐会员获取';
								$params['mark'] = '推荐会员获取了';
								$this->score_add($params);
							}
						}
					}
				}
			}
		}else{
			//没有绑定商城
			if(empty($params['invite_openid'])){
				//没有推荐人
				//trace("没有绑定商城-没有推荐人-".json_encode($params),'error');
				return false;
			}else{
				$where = [];
				$where['ur.game_id'] = $params['game_id'];
				$where['ur.open_id'] = $params['invite_openid'];
				$info = self::alias('u')->join(['eb_user_games'=>'ur'],'ur.uid=u.uid')->where($where)->field('u.uid,u.is_promoter,u.spread_uid,u.promoter_uid,promoter_link')->find();
//print_r($info);
//die;
				if(empty($info)){
					//A用户的推荐人Ｂ也没有绑定积分商城，无金牌会员Ｃ，则无操作
					if(empty($params['invite_gold_uid'])){
						//trace("推荐人没有绑定商城-无金牌会员-".json_encode($params),'error');
						return false;
					}else{
						//A用户的推荐人Ｂ也没有绑定积分商城，有金牌会员Ｃ，则金牌会员Ｃ加１０
						$params['score'] = $score * 0.1;
						$params['uid'] = $params['invite_gold_uid'];
						$params['title'] = '推荐会员获取';
						$params['mark'] = '推荐会员获取了';
						$this->score_add($params);
					}
				}else{
					if($info['is_promoter']>0){
						//A用户的推荐人Ｂ绑定了积分商城，同时是金牌会员，则推荐人Ｂ在积分商城加２５
						$params['score'] = $score * 0.25;
						$params['uid'] = $info['uid'];
						$params['title'] = '推荐会员获取';
						$params['mark'] = '推荐会员获取了';
						$this->score_add($params);
					}elseif(empty($params['invite_gold_uid'])){
						//A用户的推荐人Ｂ绑定了积分商城，没有金牌会员Ｃ，则推荐人Ｂ在积分商城加１５
						$params['score'] = $score * 0.15;
						$params['uid'] = $info['uid'];
						$params['title'] = '推荐会员获取';
						$params['mark'] = '推荐会员获取了';
						$this->score_add($params);
					}else{
						//A用户的推荐人Ｂ绑定了积分商城，有金牌会员Ｃ，则推荐人Ｂ在积分商城加１５，金牌会员Ｃ在积分商城加１０
						$params['score'] = $score * 0.15;
						$params['uid'] = $info['uid'];
						$params['title'] = '推荐会员获取';
						$params['mark'] = '推荐会员获取了';
						$this->score_add($params);
						$params['score'] = $score * 0.1;
						$params['uid'] = $params['invite_gold_uid'];
						$params['title'] = '推荐会员获取';
						$params['mark'] = '推荐会员获取了';
						$this->score_add($params);
					}

				}
			}
		}
		return true;
	}
	public function score_add($params){
		self::table("eb_user")->where("uid",$params['uid'])->update(["integral"=>['inc',$params['score']]]);
		$data = [];
		$data['uid'] = $params['uid'];
		$data['link_id'] = 1;
		$data['pm'] = 1;
		$data['title'] = $params['title'];
		$data['category'] = 'integral';
		$data['type'] = 'game_add';
		$data['add_time'] = time();
		$data['number'] = $params['score'];
		$data['balance'] = $params['score'];
		$data['mark'] = $params['mark'].$params['score'].'积分';
		$data['status'] = 1;
		self::table("eb_user_bill")->insert($data);
	}
}
