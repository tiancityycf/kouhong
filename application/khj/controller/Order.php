<?php
namespace app\qmxz\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use app\qmxz\model\Goods as GoodsModel;
use app\qmxz\model\ExchangeLog as ExchangeLogModel;

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
     * 商品发货处理
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

      public function ship()
    {
        $product_id = $this->request->get('productId');

        if(!$product_id) return;

        $model = new ExchangeLogModel();

        $res = $model->where('id',$product_id)->update(['status'=>1]);

        if($res){

            echo 'success';
        }else{

            echo 'fail';
        }
        
    }

    /**
     * EXECL导出文件
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

     public function exportExcel($expTitle,$expCellName,$expTableData){
        
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = date('Y-m-d H:i:s');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        require '../vendor/PHPExcel/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();
    
        //vendor("PHPExcel.PHPExcel");
         
        //$objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
         
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.' Export time:'.date('Y-m-d H:i:s')); 
        for($i=0;$i<$cellNum;$i++){
          $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]); 
        } 
         // Miscellaneous glyphs, UTF-8  
        for($i=0;$i<$dataNum;$i++){
         for($j=0;$j<$cellNum;$j++){
          $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
         }       
        } 
         
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); 
        $objWriter->save('php://output'); 
        exit;  

     }

    //导出Excel  
      public  function export_excel(){
          $xlsName = "订单表";
          $xlsCell = array(
            array('id','订单序号'),
            array('openid','玩家openid'),
            array('good_id','商品ID'),
            array('title','商品名称'),
            array('nickname','收件人名称'),
            array('phone','收件人手机'),
            array('region','省份'),
            array('addr','具体地址信息'),
            array('status','发货状态'),
            array('create_time','创建时间'),
         );

          $xlsData = Db::name('exchange_log')->alias('e')->join(['t_address'=>'a'],'e.address_id=a.id')->join(['t_goods'=>'g'],'e.good_id=g.id')->field('e.id,e.good_id,e.status,e.create_time,g.title,a.openid,a.nickname,a.phone,a.addr,a.region')->order('id desc')->select();
          //dump($xlsData);die;

            foreach ($xlsData as $k => $v)
            {
              $xlsData[$k]['status']=$v['status']==1?'已发货':'未发货';
              $xlsData[$k]['create_time']=date('Y-m-d H:i:s',$v['create_time']);
            }
            $this->exportExcel($xlsName,$xlsCell,$xlsData);
      }
  
}