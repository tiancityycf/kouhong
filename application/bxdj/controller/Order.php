<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\bxdj\model\Goods as GoodsModel;
use app\bxdj\model\ExchangeLog as ExchangeLogModel;

use think\cache\driver\Redis;
use think\facade\Cache;

class Order extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'exchange_log';

	public function index()
    {
    	$this->title = '订单管理';

       	list($get, $db) = [$this->request->get(), new ExchangeLogModel()];

        $db = $db->search($get);
        
       	$result = parent::_list($db, true, false, false);
        //dump($result);die;
        $this->assign('title', $this->title);

        return  $this->fetch('index', $result);
    }

   
     /**
     * 删除商品图片集
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

      public function ship()
    {
        $product_id = $this->request->get('productId');

        if(!$product_id) return;

        $model = new ExchangeLogModel();

        $res = $model->where('id',$product_id)->delete();

        if($res){

            echo 'success';
        }else{

            echo 'fail';
        }
        
    }

  
}