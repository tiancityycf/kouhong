<?php

namespace app\tzzqj\controller;

use controller\BasicAdmin;
use think\Db;

class Challenge extends BasicAdmin
{
	/**
     * 指定当前数据表
     * @var string
     */
    public $table = 'challenge_log';

    public function index()
    {
       	$this->title = '挑战记录';

       	list($get, $db) = [$this->request->get(), Db::name($this->table)];

        foreach (['successed', 'user_id'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->where([$key => $get[$key]]);
        }
        if (isset($get['score']) && $get['score'] !== '') {
            $db->where('score', '>=', $get['score']);
        }

        if (isset($get['start_time']) && $get['start_time'] !== '') {
            //list($start_start_time, $end_start_time) = explode(' - ', $get['start_time']);
            $db->whereTime('start_time', '>=', $get['start_time']);
        }

        if (isset($get['end_time']) && $get['end_time'] !== '') {
            //list($start_end_time, $end_end_time) = explode(' - ', $get['end_time']);
            $db->whereTime('start_time', '<=', $get['end_time']);
        }

        if (!isset($get['successed']) && empty($get['score']) && empty($get['start_time']) && empty($get['end_time'])) {
            $db->where('successed', '=', 1);
            $db->whereTime('start_time', 'today');
        }

        $db->order('start_time', 'desc');

        $this->assign('get', $get);

        //echo "<pre>"; print_r($db->select());exit();

       	$result = parent::_list($db, true, false, false);
        $this->assign('title', $this->title);
        return  $this->fetch('admin@challenge/index', $result);
    }
}