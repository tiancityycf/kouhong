<?php

namespace app\qmxz\controller;

use app\qmxz\model\SpecialWordWarehouse as SpecialWordWarehouseModel;
use app\qmxz\model\SpecialWarehouse as SpecialWarehouseModel;
use app\qmxz\validate\SpecialWordWarehouse as SpecialWordWarehouseValidate;
use controller\BasicAdmin;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

//整点场题库控制器类
class SpecialWordWarehouse extends BasicAdmin
{

    //字段验证
    protected function checkData($data)
    {
        $validate = new SpecialWordWarehouseValidate();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        return true;
    }

    public function index()
    {
        $this->title = '整点题库';

        list($get, $db) = [$this->request->get(), new SpecialWordWarehouseModel()];

        $db = $db->search($get);

        $this->special_warehouse_list();
        return parent::_list($db);
    }

    public function upload()
    {
        $file = request()->file('file');

        if (!$file) {
            return json_encode(['code' => 0, 'msg' => '请先选择要上传的表格']);
        }

        $info = $file->validate(['ext' => 'xlsx']);
        if ($info) {
            $file_info   = $file->getInfo();
            $reader      = new Xlsx();
            $spreadsheet = $reader->load($file_info['tmp_name']);

            $data = $spreadsheet->getActiveSheet()->toArray();

            $tmp_data = ['special_warehouse_id', 'title', 'des', 'options'];

            $first_data = $data[0];
            if ($tmp_data != $first_data) {
                return json_encode(['code' => 0, 'msg' => '表头和字段对不上！']);
            }

            unset($data[0]);

            //$word_arr = $this->getWordArr();

            $arr = array_chunk($data, 1000);

            $i = 0;
            $j = 0;

            $save_arr = $arr[0];
            $saveData       = [];
            foreach ($save_arr as $key => $value) {
                $i++;
                foreach ($value as $k => $v) {
                    if($tmp_data[$k] == 'options'){
                        $options_arr = explode(",",$v);
                        $saveData[$key][$tmp_data[$k]] = json_encode($options_arr, JSON_UNESCAPED_UNICODE);
                    }else{
                        $saveData[$key][$tmp_data[$k]] = $v;
                    }
                }
                if(!empty($value)){
                    $saveData[$key]['create_time'] = time();
                }
            }
            $j = count($saveData);
            $special_word_warehouse = new SpecialWordWarehouseModel();
            $special_word_warehouse->saveAll($saveData);

            return json_encode(['code' => 1, 'msg' => '数据保存成功<font color="green">' . $i . '</font>条，<font color="red">' . $j . '</font>条数据为空或者已经存在']);
        } else {
            return json_encode(['code' => 0, 'msg' => '上传文件格式错误']);
        }
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $data['options']     = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
            $model               = new SpecialWordWarehouseModel();
            $data['create_time'] = time();

            if ($this->checkData($data) === true && $model->save($data) != false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_warehouse_list();
        return $this->fetch('form', ['vo' => $data]);
    }

    public function edit()
    {
        $get_data = $this->request->get();

        $vo        = SpecialWordWarehouseModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
            $post_data['options'] = json_encode($post_data['options'], JSON_UNESCAPED_UNICODE);
            if ($this->checkData($post_data) === true && $vo->save($post_data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        $this->special_warehouse_list();
        return $this->fetch('edit', ['vo' => $vo->getdata()]);
    }

    //删除
    public function del()
    {
        $data = $this->request->post();
        if ($data) {
            $model = SpecialWordWarehouseModel::get($data['id']);
            if ($model->delete() !== false) {
                $this->success("删除成功！", '');
            }
        }

        $this->error("删除失败，请稍候再试！");
    }

    protected function special_warehouse_list()
    {
        $data = SpecialWarehouseModel::column('title', 'id');

        $this->assign('special_warehouse_list', $data);
    }
}
