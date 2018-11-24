<?php

namespace app\khj\controller\api\v1_0_1;

use app\khj\service\v1_0_1\DailyTask as DailyTaskService;
use controller\BasicController;
use think\facade\Request;

/**
 * 每日任务控制器类
 */
class DailyTask extends BasicController
{
    public function taskList()
    {
        require_params('user_id');
        $data = Request::param();

        $daily_task = new DailyTaskService($this->configData);
        //每日任务列表
        $result = $daily_task->taskList($data);

        return result(200, 'ok', $result);
    }

    public function taskFinishRecord()
    {
        require_params('user_id', 'pid', 'task_id', 'is_new', 'iv', 'encryptedData');
        $data = Request::param();

        $daily_task = new DailyTaskService($this->configData);
        //记录任务完成情况
        $result = $daily_task->taskFinishRecord($data);

        return result(200, 'ok', $result);
    }
}
