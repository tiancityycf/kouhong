<?php
namespace app\bxdj\controller;

use controller\BasicAdmin;

use app\bxdj\model\TemplateMsg as TemplateMsgModel;

use service\WangSuService;

use think\Db;
use think\cache\driver\Redis;
use think\facade\Cache;


class TemplateMsg extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'template_msg';

	public function index()
    {
    	$this->title = '微信模板消息';

       	list($get, $db) = [$this->request->get(), new TemplateMsgModel()];

        //$db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
       	return  $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            // dump($data);exit;
            $postData = [];
            $postData['template_id'] = $data['template_id'];
            $postData['status'] = $data['status'];
            $postData['title'] = Db::name('wxtemplate')->where('template_id', $data['template_id'])->value('title');
            unset($data['template_id']);
            unset($data['status']);
            $postData['content'] = serialize($data);
            $model = new TemplateMsgModel();
            if ($model->save($postData) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        //模板分类
        $template_list = Db::name('wxtemplate')->where('status', 1)->select();
        $template_list_arr = [];
        foreach ($template_list as $key => $value) {
            $template_list_arr[$value['template_id']] = $value['title'];
        }
        $this->assign('template_list_arr', $template_list_arr);

        $template_exam_arr = [];
        foreach ($template_list as $key => $value) {
            $template_exam_arr[$value['template_id']] = $value['example'];
        }
        $this->assign('template_exam_arr', $template_exam_arr);
        

        return  $this->fetch('form', ['vo' => $data]);

    }

    public function getTemplateList()
    {

        $get_data = $this->request->get();
        $id = $get_data['id'];

        //模板分类
        $template_list = Db::name('wxtemplate')->where('status', 1)->select();
        $mbdata = '';
        $mbdata_arr = [];
        $example = [];
        foreach ($template_list as $key => $value) {
            preg_match_all('/\{{(.+?)\.DATA}}/is', $value['content'], $mbdata);
            $mbdata_arr[$value['template_id']] = $mbdata;
            $example[$value['template_id']] = $value['title'].'<br/><br/>'.str_replace('\n', '<br/><br/>', $value['example']);
        }

        $template_arr = [];
        if(!empty($mbdata_arr)){
            foreach ($mbdata_arr as $key => $value) {
                $template_arr[$key] = $value[1];
                $preg_name[$key] = $value[0];
            }
        }
        $tmp_name = [];
        foreach ($template_list as $key => $value) {
            foreach ($preg_name as $k => $v) {
                if($value['template_id'] == $k){
                    $tmp_name[] = explode(',', rtrim(str_replace('\n', '', str_replace($v, ',', $value['content'])), ','));
                }
            }
        }
        $template_arr1 = [];
        
        if($id){
            $content_str = Db::name('template_msg')->where('id', $id)->value('content');
            $content_arr = unserialize($content_str);
            $content_arr1 = [];
            foreach ($content_arr as $key => $value) {
                $content_arr1[] = $value;
            }
            // dump($content_arr1);exit;
        }

        $m = 0;
        foreach ($template_arr as $key => $value) {
            
            foreach ($value as $k => $v) {

                $template_arr1[$key][$k]['title'] = $tmp_name[$m][$k];
                $template_arr1[$key][$k]['name'] = $v;
                if($id){
                    $template_arr1[$key][$k]['color'] = $content_arr1[$k]['color'];
                    $template_arr1[$key][$k]['value'] = $content_arr1[$k]['value'];
                }else{
                    $template_arr1[$key][$k]['color'] = '';
                    $template_arr1[$key][$k]['value'] = '';
                }
                
            }
            $m += 1;
        }
        
       // dump($template_arr1); dump($example); exit;
        return [
            'template_arr' => $template_arr1,
            'example' => $example
        ];
    }

    public function edit()
    {
        $get_data = $this->request->get();


        $model = new TemplateMsgModel();

        $vo = $model->where('id',$get_data['id'])->find();

        $post_data = $this->request->post();
        if ($post_data) {
            $data = [];
            $data['template_id'] = $post_data['template_id'];
            $data['status'] = $post_data['status'];
            $data['id'] = $post_data['id'];
            $data['title'] = Db::name('wxtemplate')->where('template_id', $post_data['template_id'])->value('title');
            unset($post_data['template_id']);
            unset($post_data['status']);
            unset($post_data['id']);
            $data['content'] = serialize($post_data);
            if ($vo->save($data) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }
        //模板分类
        $template_list = Db::name('wxtemplate')->where('status', 1)->select();
        $template_list_arr = [];
        foreach ($template_list as $key => $value) {
            $template_list_arr[$value['template_id']] = $value['title'];
        }
        $this->assign('template_list_arr', $template_list_arr);

        $template_exam_arr = [];
        foreach ($template_list as $key => $value) {
            $template_exam_arr[$value['template_id']] = $value['example'];
        }

        $this->assign('template_exam_arr', $template_exam_arr);
        return  $this->fetch('form', ['vo' => $vo]);

    }

    /**
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $data = $this->request->post();
        if ($data) {
            $model = TemplateMsgModel::get($data['id']);
            $model->status = 0;
            if ($model->save() !== false) {
                $this->success("禁用成功！", '');
            }
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
        $data = $this->request->post();
        if ($data) {
            $model = TemplateMsgModel::get($data['id']);
            $model->status = 1;
            if ($model->save() !== false) {
                $this->success("启用成功！", '');
            }
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

            // $tmp_data = ['word', 'mix_num', 'mix_char', 'pinyin', 'intro', 'caution', 'level'];
            $tmp_data = ['type', 'img', 'content'];

            $first_data = $data[0];

            if ($tmp_data != $first_data) {
                return json_encode(['code' => 0, 'msg' => '表头和字段对不上！']);
            }

            unset($data[0]);

            //$word_arr = $this->getWordArr();

            $arr = array_chunk($data, 1000);

            $i = 0;
            $j = 0;

            foreach ($arr as $key => $value) {
                $batch_data = [];
                foreach ($value as $k => $v) {
                    if ($v[0] != '') {
                        foreach ($v as $m => $n) {
                            if ($m == 0) {
                                $batch_data[$k][$tmp_data[$m]] = trim($n);
                            } else {
                                $batch_data[$k][$tmp_data[$m]] = $n;
                            }
                        }

                        $i++;
                        //array_push($word_arr, (trim($v[0]).'-'.$v[1]));
                    } else {
                        $j++;
                    }
                }
                $word_model = new TemplateMsgModel();
                $word_model->saveAll($batch_data);
            }

            // foreach ($arr as $key => $value) {
            //     $batch_data = [];
            //     foreach ($value as $k => $v) {
            //         if ($v[0] != '' && !in_array((trim($v[0]).'-'.$v[1]), $word_arr)) {
            //             foreach ($v as $m => $n) {
            //                 if ($m == 0) {
            //                     $batch_data[$k][$tmp_data[$m]] = trim($n);
            //                 } else {
            //                     $batch_data[$k][$tmp_data[$m]] = $n;
            //                 }
            //             }

            //             $i++;
            //             array_push($word_arr, (trim($v[0]).'-'.$v[1]));
            //         } else {
            //             $j++;
            //         }
            //     }
            //     $word_model = new TemplateMsgModel();
            //     $word_model->save($batch_data);
            // }

            // $this->redisSave();

            return json_encode(['code' => 1, 'msg' => '数据保存成功<font color="green">'. $i .'</font>条，<font color="red">'.$j.'</font>条数据为空或者已经存在']);
        } else {
            return json_encode(['code' => 0, 'msg' => '上传文件格式错误']);
        }
    }

    public function upload_img()
    {
        $file = request()->file('file');
        $info = $file->validate(['ext'=>'jpg,png,gif']);
        if($info){
            $file_info = $file->getInfo();

            $obj = new WangSuService();

            $res = $obj->upload($file_info);

            return $res;

        } else  {
            return json_encode(['code' => 0, 'msg' => '上传文件格式错误']);
        }
    }

    //获取数据库的词语，返回数组
    protected function getWordArr()
    {
        // $arr = TemplateMsgModel::column(['id','concat(word,"-",mix_num)'=>'str']);
        $arr = TemplateMsgModel::column(['title']);
        return $arr;
    }

    //判断是否为学科类
    protected function checkData($data, $field)
    {
        if(!$data[$field] || ($data[$field] == '')){
            $this->error("学科类需填写教师类型！");
        }
    }
}