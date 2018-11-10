<?php
namespace app\qmxz\controller;

use app\qmxz\model\TemplateMsg as TemplateMsgModel;
use controller\BasicAdmin;
use think\Db;

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

        $db = $db->search($get);

        $result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return $this->fetch('index', $result);
    }

    public function add()
    {
        $data = $this->request->post();
        if ($data) {
            $postData                = [];
            $postData['template_id'] = $data['template_id'];
            $postData['status']      = $data['status'];
            $postData['title']       = Db::name('template')->where('template_id', $data['template_id'])->value('title');
            unset($data['template_id']);
            unset($data['status']);
            $postData['content'] = serialize($data);
            $model               = new TemplateMsgModel();
            if ($model->save($postData) !== false) {
                $this->success('恭喜, 数据保存成功!', '');
            } else {
                $this->error('数据保存失败, 请稍候再试!');
            }
        }

        //模板分类
        $template_list     = Db::name('template')->where('status', 1)->select();
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

        return $this->fetch('form', ['vo' => $data]);

        //return $this->_form($this->table, 'form');
    }

    public function getTemplateList()
    {
        $get_data = $this->request->get();
        $id       = $get_data['id'];
        //模板分类
        $template_list = Db::name('template')->where('status', 1)->select();
        $mbdata        = '';
        $mbdata_arr    = [];
        $example       = [];
        foreach ($template_list as $key => $value) {
            preg_match_all('/\{{(.+?)\.DATA}}/is', $value['content'], $mbdata);
            $mbdata_arr[$value['template_id']] = $mbdata;
            $example[$value['template_id']]    = $value['title'] . '<br/><br/>' . str_replace('\n', '<br/><br/>', $value['example']);
        }
        $template_arr = [];
        if (!empty($mbdata_arr)) {
            foreach ($mbdata_arr as $key => $value) {
                $template_arr[$key] = $value[1];
                $preg_name[$key]    = $value[0];
            }
        }
        $tmp_name = [];
        foreach ($template_list as $key => $value) {
            foreach ($preg_name as $k => $v) {
                if ($value['template_id'] == $k) {
                    $tmp_name[] = explode(',', rtrim(str_replace('\n', '', str_replace($v, ',', $value['content'])), ','));
                }
            }
        }
        $template_arr1 = [];

        if ($id) {
            $content_str  = Db::name('template_msg')->where('id', $id)->value('content');
            $content_arr  = unserialize($content_str);
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
                $template_arr1[$key][$k]['name']  = $v;
                if ($id) {
                    $template_arr1[$key][$k]['color'] = isset($content_arr1[$k]['color']) ? $content_arr1[$k]['color'] : '';
                    $template_arr1[$key][$k]['value'] = isset($content_arr1[$k]['value']) ? $content_arr1[$k]['value'] : '';
                } else {
                    $template_arr1[$key][$k]['color'] = '';
                    $template_arr1[$key][$k]['value'] = '';
                }
            }
            $m += 1;
        }
        return [
            'template_arr' => $template_arr1,
            'example'      => $example,
        ];
    }

    public function edit()
    {
        $get_data  = $this->request->get();
        $vo        = TemplateMsgModel::get($get_data['id']);
        $post_data = $this->request->post();
        if ($post_data) {
            $data                = [];
            $data['template_id'] = $post_data['template_id'];
            $data['status']      = $post_data['status'];
            $data['id']          = $post_data['id'];
            $data['title']       = Db::name('template')->where('template_id', $post_data['template_id'])->value('title');
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
        $template_list     = Db::name('template')->where('status', 1)->select();
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
        return $this->fetch('form', ['vo' => $vo->getData()]);
        //return $this->_form($this->table, 'form');
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
            $model         = TemplateMsgModel::get($data['id']);
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
            $model         = TemplateMsgModel::get($data['id']);
            $model->status = 1;
            if ($model->save() !== false) {
                $this->success("启用成功！", '');
            }
        }

        $this->error("启用失败，请稍候再试！");
    }
}
