<?php
namespace app\qmxz\controller;

use app\qmxz\model\Address as AddressModel;
use app\qmxz\model\SpecialPrize as SpecialPrizeModel;
use app\qmxz\model\User as UserModel;
use app\qmxz\model\UserSpecialPrize as UserSpecialPrizeModel;
use controller\BasicAdmin;

class UserSpecialPrize extends BasicAdmin
{
    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'user_special_prize';

    public function index()
    {
        $this->title = '中奖发货';

        list($get, $db) = [$this->request->get(), new UserSpecialPrizeModel()];

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        if (count($result['list']) > 0) {
            foreach ($result['list'] as $key => $value) {
                //用户信息
                $user_info                      = UserModel::where('id', $value['user_id'])->find();
                $result['list'][$key]['avatar'] = $user_info['avatar'];
                $result['list'][$key]['openid'] = $user_info['openid'];
                //奖品信息
                $prize_info                    = SpecialPrizeModel::where('id', $value['prize_id'])->find();
                $result['list'][$key]['title'] = $prize_info['name'];
                $result['list'][$key]['img']   = $prize_info['img'];
                //地址信息
                $user_address = AddressModel::where('openid', $user_info['openid'])->where('status', 1)->find();
                if ($user_address) {
                    $result['list'][$key]['region']   = $user_address['region'];
                    $result['list'][$key]['addr']     = $user_address['addr'];
                    $result['list'][$key]['nickname'] = $user_address['nickname'];
                    $result['list'][$key]['phone']    = $user_address['phone'];
                } else {
                    $result['list'][$key]['region']   = '';
                    $result['list'][$key]['addr']     = '';
                    $result['list'][$key]['nickname'] = '无';
                    $result['list'][$key]['phone']    = '无';
                }
            }
        }

        // dump($result['list']);die;
        $this->assign('title', $this->title);

        return $this->fetch('index', $result);
    }

    /**
     * 商品发货处理
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function ship()
    {
        $product_id = $this->request->get('productId');

        if (!$product_id) {
            return;
        }

        $model = new UserSpecialPrizeModel();

        $res = $model->where('id', $product_id)->update(['status' => 1]);

        if ($res) {

            echo 'success';
        } else {

            echo 'fail';
        }

    }

    /**
     * EXECL导出文件
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */

    public function exportExcel($expTitle, $expCellName, $expTableData)
    {

        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
        $fileName = date('Y-m-d H:i:s'); //or $xlsTitle 文件名称可根据自己情况设定
        $cellNum  = count($expCellName);
        $dataNum  = count($expTableData);

        require '../vendor/PHPExcel/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();

        //vendor("PHPExcel.PHPExcel");

        //$objPHPExcel = new PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //合并单元格
        // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.' Export time:'.date('Y-m-d H:i:s'));
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;

    }

    //导出Excel
    public function export_excel()
    {
        $xlsName = "中奖列表";
        $xlsCell = array(
            array('id', '中奖序号'),
            array('openid', '用户openid'),
            array('prize_id', '奖品ID'),
            array('title', '奖品名称'),
            array('nickname', '收件人名称'),
            array('phone', '收件人手机'),
            array('region', '省份'),
            array('addr', '具体地址信息'),
            array('status', '发货状态'),
            array('addtime', '创建时间'),
        );

        $export_list = UserSpecialPrizeModel::order('id desc')->select();
        foreach ($export_list as $key => $value) {
            //用户信息
            $user_info                   = UserModel::where('id', $value['user_id'])->find();
            $export_list[$key]['avatar'] = $user_info['avatar'];
            $export_list[$key]['openid'] = $user_info['openid'];
            //奖品信息
            $prize_info                 = SpecialPrizeModel::where('id', $value['prize_id'])->find();
            $export_list[$key]['title'] = $prize_info['name'];
            $export_list[$key]['img']   = $prize_info['img'];
            //地址信息
            $user_address = AddressModel::where('openid', $user_info['openid'])->where('status', 1)->find();
            if ($user_address) {
                $export_list[$key]['region']   = $user_address['region'];
                $export_list[$key]['addr']     = $user_address['addr'];
                $export_list[$key]['nickname'] = $user_address['nickname'];
                $export_list[$key]['phone']    = $user_address['phone'];
            } else {
                $export_list[$key]['region']   = '';
                $export_list[$key]['addr']     = '';
                $export_list[$key]['nickname'] = '无';
                $export_list[$key]['phone']    = '无';
            }
        }

        $xlsData = [];

        foreach ($export_list as $key => $value) {
            $xlsData[$key]['id']       = $value['id'];
            $xlsData[$key]['openid']   = $value['openid'];
            $xlsData[$key]['prize_id'] = $value['prize_id'];
            $xlsData[$key]['title']    = $value['title'];
            $xlsData[$key]['nickname'] = $value['nickname'];
            $xlsData[$key]['phone']    = $value['phone'];
            $xlsData[$key]['region']   = $value['region'];
            $xlsData[$key]['addr']     = $value['addr'];
            if ($value['status'] == 1) {
                $xlsData[$key]['status'] = '已发货';
            } else {
                $xlsData[$key]['status'] = '未发货';
            }

            $xlsData[$key]['addtime'] = $value['addtime'];
        }

        $this->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
