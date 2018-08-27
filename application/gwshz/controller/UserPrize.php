<?php

namespace app\zqszw\controller;

use controller\BasicAdmin;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserPrize extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'user_prize';

    public function index()
    {
    	$this->title = '礼物领取记录';

       	list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['name'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        if (isset($get['create_time']) && $get['create_time'] !== '') {
            list($start_create_time, $end_create_time) = explode(' - ', $get['create_time']);
            $db->whereBetweenTime('create_time', "{$start_create_time}", "{$end_create_time}");
        }

        $db->order('create_time', 'desc');

        $this->assign('data',$this->request->get());

       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@user_prize/index', $result);

    }

    public function load()
    {
        $data = $this->request->get();

        $db = Db::name($this->table)
            ->field('name, phone, count(phone) as num, address')
            ->group('phone');

        if (isset($data['name']) && $data['name'] !== '') {
            $db->whereLike('name', "%{$data['name']}%");
        }

        if (isset($data['create_time']) && $data['create_time'] !== '') {
            list($start_create_time, $end_create_time) = explode(' - ', $data['create_time']);
            $db->whereBetweenTime('create_time', $start_create_time, $end_create_time);
        }

        $list = $db->select();


        $spreadsheet = new Spreadsheet();

        $head = ['用户名字', '手机号码', '领取次数', '地址信息'];
        $key = ['name', 'phone', 'num', 'address'];

        // Add some data
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', $head[0])
            ->setCellValue('B1', $head[1])
            ->setCellValue('C1', $head[2])
            ->setCellValue('D1', $head[3]);


        $count = count($list);

        for ($i = 0; $i < $count; $i++) {
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($i+2), $list[$i][$key[0]]);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($i+2), $list[$i][$key[1]]);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($i+2), $list[$i][$key[2]]);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($i+2), $list[$i][$key[3]]);
        }

        $filename = "user_prize_".date('Y-m-d', time()).".xlsx";
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        header("Content-type: application/octet-stream");
        header('Content-Disposition:attachment;filename = '.$filename);
        $writer->save('php://output');
    }

}