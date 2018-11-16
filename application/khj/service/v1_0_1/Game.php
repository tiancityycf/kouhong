<?php

namespace app\khj\service\v1_0_1;

use app\khj\model\GameRecord as GameRecordModel;
use think\Db;

/**
 * 游戏服务类
 */
class Game
{
    protected $configData;

    public function __construct($configData = [])
    {
        $this->configData = $configData;
    }

    /**
     * 游戏结束
     * @param  [type] $data 接收参数
     * @return [type]       [description]
     */
    public function end($data)
    {
        // 开启事务
        Db::startTrans();
        try {
            $game_record = GameRecordModel::where('user_id', $data['user_id'])->where('mode', $data['mode'])->where('checkpoint', $data['checkpoint'])->where('dday', date('Ymd'))->find();
            if ($game_record) {
                //存在记录，更新记录
            } else {
                //不存在记录，保存记录
                $game_record             = new GameRecordModel();
                $game_record->user_id    = $data['user_id'];
                $game_record->mode       = $data['mode'];
                $game_record->checkpoint = $data['checkpoint'];
                $game_record->is_win     = $data['is_win'];
                $game_record->dday       = date('Ymd');
                $game_record->save();
            }
            Db::commit();
            return [
                'status' => 1,
                'msg'    => 'ok',
                'data'   => '',
            ];
        } catch (\Exception $e) {
            lg($e);
            Db::rollback();
            return [
                'status' => 0,
                'msg'    => 'fail',
                'data'   => '',
            ];
        }
    }
}
