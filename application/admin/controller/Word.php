<?php
namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Db;
use think\facade\Cache;

class Word extends BasicAdmin
{

	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'word';

    public $word_key = 'xsc:829fc:word:level:';


    public function index()
    {
       	$this->title = '词库管理';

       	list($get, $db) = [$this->request->get(), Db::name($this->table)];
        foreach (['word'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        if (isset($get['level']) && $get['level'] !== '') {
            $db->where('level', '=', $get['level']);
        }

       	return parent::_list($db);
    }

    public function add()
    {
        $data = $this->request->post();
        if($data){
             if (Db::name($this->table)->strict(false)->insert($data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        
        return  $this->fetch('form', ['vo' => $data]);

    	//return $this->_form($this->table, 'form');
    }

    public function edit()
    {
        $get_data = $this->request->get();
        
        $vo = Db::name($this->table)->where('id',$get_data['id'])->find();

        $post_data = $this->request->post();
        if($post_data){
            if (Db::name($this->table)->where(['id' => $get_data['id']])->update($post_data) !== false && $this->redisSave()) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        return  $this->fetch('form', ['vo' => $vo]);
    	//return $this->_form($this->table, 'form');
    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
    	if (DataService::update($this->table) && $this->redisSave()) {
            $this->success("禁用成功！", '');
        }
        $this->error("禁用失败，请稍候再试！");
    }

    /**
     * 启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
    	if (DataService::update($this->table) && $this->redisSave()) {
            $this->success("启用成功！", '');
        }
        $this->error("启用失败，请稍候再试！");
    }

    public function upload()
    {
        $file = request()->file('file');

        if (!$file) {
            return json_encode(['code' => 0, 'msg' => '请先选择要上传的表格']);
        }
        
        $info = $file->validate(['ext'=>'xlsx']);
        if ($info) {
            $file_info = $file->getInfo();
            $reader = new Xlsx();
            $spreadsheet = $reader->load($file_info['tmp_name']);

            $data = $spreadsheet->getActiveSheet()->toArray();

            $tmp_data = ['word', 'mix_num', 'mix_char', 'pinyin', 'intro', 'caution', 'level'];

            $first_data = $data[0];

            if ($tmp_data != $first_data) {
                return json_encode(['code' => 0, 'msg' => '表头和字段对不上！']);
            }

            unset($data[0]);

            $word_arr = $this->getWordArr();

            $arr = array_chunk($data, 1000);

            $i = 0;
            $j = 0;

            foreach ($arr as $key => $value) {
                $batch_data = [];
                foreach ($value as $k => $v) {
                    if ($v[0] != '' && !in_array((trim($v[0]).'-'.$v[1]), $word_arr)) {
                        foreach ($v as $m => $n) {
                            if ($m == 0) {
                                $batch_data[$k][$tmp_data[$m]] = trim($n);
                            } else {
                                $batch_data[$k][$tmp_data[$m]] = $n;
                            }
                        }

                        $i++;
                        array_push($word_arr, (trim($v[0]).'-'.$v[1]));
                    } else {
                        $j++;
                    }
                }

                Db::name($this->table)->insertALL($batch_data);
            }
            $this->redisSave();

            return json_encode(['code' => 1, 'msg' => '数据保存成功<font color="green">'. $i .'</font>条，<font color="red">'.$j.'</font>条数据为空或者已经存在']);
        } else {
            return json_encode(['code' => 0, 'msg' => '上传文件格式错误']);
        }
    }

    protected function redisSave()
    {
        $cache = Cache::init();
        $redis = $cache->handler();

        $level_list = Db::name($this->table)->group('level')->column('level');
        
        foreach ($level_list as $level) {
            $id_list = Db::name($this->table)->where('level', '=', $level)->where('status', '=', 1)->column('id');
            $redis->delete($this->word_key.$level);
            foreach ($id_list as $id) {
                $redis->sadd($this->word_key.$level,$id);
            }
        }

        return true;
    }

    //获取数据库的词语，返回数组
    protected function getWordArr()
    {
        $arr = Db::name($this->table)->column(['id','concat(word,"-",mix_num)'=>'str']);
        return $arr;
    }
}
